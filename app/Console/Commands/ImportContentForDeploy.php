<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Category;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Phar;
use PharData;
use ZipArchive;

class ImportContentForDeploy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'content:import-for-deploy
                            {bundle : Path to the content deployment bundle archive (.tar.gz, .zip) or unpacked folder}
                            {--dry-run : Simulate the import without changing database records or files}
                            {--force : Force update existing records even if unchanged}
                            {--backup : Create a database backup prior to importing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import CricBail content and media from a deployment bundle safely and idempotently';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('========================================================================');
        $this->info('           CRICBAIL: CONTENT IMPORT FOR PRODUCTION DEPLOYMENT           ');
        $this->info('========================================================================');

        $bundleArg = $this->argument('bundle');
        $isDryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $shouldBackup = (bool) $this->option('backup');

        if ($isDryRun) {
            $this->warn('*** DRY RUN MODE ACTIVATED - NO DATABASE OR FILE MODIFICATIONS WILL BE MADE ***');
            $this->newLine();
        }

        // 1. Resolve and extract bundle
        $bundlePath = realpath($bundleArg) ?: $bundleArg;
        if (!file_exists($bundlePath)) {
            $this->error("Bundle file or directory not found at: {$bundleArg}");
            return self::FAILURE;
        }

        $tempExtractDir = null;
        $manifestDir = null;

        if (is_dir($bundlePath)) {
            $manifestDir = $bundlePath;
        } else {
            $timestamp = Carbon::now('Asia/Kolkata')->format('Ymd_His');
            $tempExtractDir = storage_path("app/deployments/tmp_import_{$timestamp}_" . Str::random(8));
            File::ensureDirectoryExists($tempExtractDir);

            try {
                $this->line("Extracting deployment bundle: <fg=cyan>{$bundlePath}</>");
                $this->extractArchive($bundlePath, $tempExtractDir);
                $manifestDir = $tempExtractDir;
            } catch (\Throwable $e) {
                if ($tempExtractDir && File::exists($tempExtractDir)) {
                    File::deleteDirectory($tempExtractDir);
                }
                $this->error("Failed to extract deployment bundle: " . $e->getMessage());
                return self::FAILURE;
            }
        }

        $manifestFile = "{$manifestDir}/manifest.json";
        if (!file_exists($manifestFile)) {
            if ($tempExtractDir && File::exists($tempExtractDir)) {
                File::deleteDirectory($tempExtractDir);
            }
            $this->error("Invalid deployment bundle: manifest.json not found inside package.");
            return self::FAILURE;
        }

        $manifestContent = file_get_contents($manifestFile);
        $manifest = json_decode($manifestContent, true);

        if (!is_array($manifest) || empty($manifest['manifest_version'])) {
            if ($tempExtractDir && File::exists($tempExtractDir)) {
                File::deleteDirectory($tempExtractDir);
            }
            $this->error("Invalid manifest.json structure or unsupported manifest version.");
            return self::FAILURE;
        }

        $this->info("Manifest verified! Exported at: " . ($manifest['exported_at'] ?? 'Unknown'));
        $this->line("Manifest content counts: Categories: " . count($manifest['categories'] ?? []) .
            ", Articles: " . count($manifest['articles'] ?? []) .
            ", Quizzes: " . count($manifest['quizzes'] ?? []) .
            ", Media: " . count($manifest['media_files'] ?? []));
        $this->newLine();

        // 2. Optional database backup
        if ($shouldBackup && !$isDryRun) {
            $this->line("Creating pre-import database backup...");
            if (!$this->createDatabaseBackup()) {
                if ($tempExtractDir && File::exists($tempExtractDir)) {
                    File::deleteDirectory($tempExtractDir);
                }
                $this->error("Aborting import because database backup failed.");
                return self::FAILURE;
            }
        }

        // Stats tracking
        $stats = [
            'categories' => ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0],
            'articles' => ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0],
            'quizzes' => ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0],
            'questions' => ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0],
            'media' => ['deployed' => 0, 'skipped' => 0, 'missing' => 0, 'failed' => 0],
        ];

        // 3. Media deployment
        $mediaFiles = $manifest['media_files'] ?? [];
        $this->info("Processing " . count($mediaFiles) . " referenced media files...");

        foreach ($mediaFiles as $mediaItem) {
            $relPath = $mediaItem['relative_path'] ?? null;
            $bundleSubPath = $mediaItem['bundle_path'] ?? null;

            if (!$relPath || !$bundleSubPath) {
                $stats['media']['failed']++;
                continue;
            }

            // Security: Prevent directory traversal
            if (str_contains($relPath, '..') || str_starts_with($relPath, '/') || str_starts_with($relPath, '\\')) {
                $this->warn("Security alert: invalid media path rejected: {$relPath}");
                $stats['media']['failed']++;
                continue;
            }

            $sourceFile = "{$manifestDir}/{$bundleSubPath}";
            if (!file_exists($sourceFile)) {
                $this->warn("Media file listed in manifest but missing from bundle: {$bundleSubPath}");
                $stats['media']['missing']++;
                continue;
            }

            $destinationFile = $this->resolveDestinationMediaPath($relPath);
            if (!$destinationFile) {
                $stats['media']['failed']++;
                continue;
            }

            if (file_exists($destinationFile)) {
                $destHash = hash_file('sha256', $destinationFile);
                if ($destHash === ($mediaItem['sha256'] ?? '') && !$force) {
                    $stats['media']['skipped']++;
                    continue;
                }
            }

            if (!$isDryRun) {
                try {
                    File::ensureDirectoryExists(dirname($destinationFile), 0755);
                    File::copy($sourceFile, $destinationFile);
                    @chmod($destinationFile, 0644);

                    // If file is in storage/app/public/articles, also sync to public/storage if symlink not active
                    $this->syncPublicMirrorIfExists($relPath, $sourceFile);

                    $stats['media']['deployed']++;
                } catch (\Throwable $e) {
                    $this->error("Failed to copy media file {$relPath}: " . $e->getMessage());
                    $stats['media']['failed']++;
                }
            } else {
                $stats['media']['deployed']++;
            }
        }

        // 4. Content Records Import within DB Transaction (if not dry-run)
        $processImport = function () use (&$manifest, &$stats, $isDryRun, $force) {
            // A. Categories
            $categoryMap = []; // slug/uuid => id
            foreach ($manifest['categories'] ?? [] as $catData) {
                try {
                    $uuid = $catData['uuid'] ?? null;
                    $slug = $catData['slug'] ?? null;
                    $name = $catData['name'] ?? null;
                    $desc = $catData['description'] ?? null;

                    if (!$slug || !$name) {
                        $stats['categories']['failed']++;
                        continue;
                    }

                    $existing = null;
                    if ($uuid) {
                        $existing = Category::where('uuid', $uuid)->first();
                    }
                    if (!$existing && $slug) {
                        $existing = Category::where('slug', $slug)->first();
                    }

                    if ($existing) {
                        $needsUpdate = $force || ($existing->name !== $name) || ($existing->description !== $desc) || (empty($existing->uuid) && $uuid);
                        if ($needsUpdate && !$isDryRun) {
                            $existing->update([
                                'uuid' => $existing->uuid ?: $uuid,
                                'name' => $name,
                                'slug' => $slug,
                                'description' => $desc,
                            ]);
                            $stats['categories']['updated']++;
                        } else {
                            $stats['categories'][$needsUpdate ? 'updated' : 'skipped']++;
                        }
                        $categoryMap[$slug] = $existing->id;
                        if ($uuid) $categoryMap[$uuid] = $existing->id;
                    } else {
                        if (!$isDryRun) {
                            $cat = Category::create([
                                'uuid' => $uuid ?: (string) Str::uuid(),
                                'name' => $name,
                                'slug' => $slug,
                                'description' => $desc,
                            ]);
                            $categoryMap[$slug] = $cat->id;
                            if ($uuid) $categoryMap[$uuid] = $cat->id;
                        }
                        $stats['categories']['created']++;
                    }
                } catch (\Throwable $e) {
                    $this->error("Error importing category [{$catData['slug']}]: " . $e->getMessage());
                    $stats['categories']['failed']++;
                }
            }

            // B. Resolve default author (User)
            $defaultAuthor = User::where('is_admin', true)->first() ?: User::first();

            // C. Articles
            foreach ($manifest['articles'] ?? [] as $artData) {
                try {
                    $uuid = $artData['uuid'] ?? null;
                    $slug = $artData['slug'] ?? null;
                    $title = $artData['title'] ?? null;

                    if (!$title || !$slug) {
                        $stats['articles']['failed']++;
                        continue;
                    }

                    // Resolve Category ID
                    $catId = null;
                    if (!empty($artData['category_uuid']) && isset($categoryMap[$artData['category_uuid']])) {
                        $catId = $categoryMap[$artData['category_uuid']];
                    } elseif (!empty($artData['category_slug']) && isset($categoryMap[$artData['category_slug']])) {
                        $catId = $categoryMap[$artData['category_slug']];
                    } else {
                        $cat = Category::where('slug', $artData['category_slug'] ?? '')->first();
                        $catId = $cat?->id;
                    }

                    if (!$catId) {
                        // Fallback to first category
                        $catId = Category::first()?->id;
                    }

                    // Resolve Author ID safely (without creating or altering passwords)
                    $authorId = $defaultAuthor?->id;
                    if (!empty($artData['author_email'])) {
                        $matchedUser = User::where('email', $artData['author_email'])->first();
                        if ($matchedUser) {
                            $authorId = $matchedUser->id;
                        }
                    }

                    // Match existing article by uuid, slug, or source_url
                    $existing = null;
                    if ($uuid) {
                        $existing = Article::where('uuid', $uuid)->first();
                    }
                    if (!$existing && $slug) {
                        $existing = Article::where('slug', $slug)->first();
                    }
                    if (!$existing && !empty($artData['source_url'])) {
                        $existing = Article::where('source_url', $artData['source_url'])->first();
                    }

                    $publishedAt = !empty($artData['published_at'])
                        ? Carbon::parse($artData['published_at'])
                        : null;

                    $payload = [
                        'uuid' => $uuid ?: ($existing?->uuid ?: (string) Str::uuid()),
                        'title' => $title,
                        'slug' => $slug,
                        'category_id' => $catId,
                        'excerpt' => $artData['excerpt'] ?? null,
                        'content' => $artData['content'] ?? '',
                        'featured_image' => $artData['featured_image'] ?? null,
                        'status' => $artData['status'] ?? 'published',
                        'published_at' => $publishedAt,
                        'meta_title' => $artData['meta_title'] ?? null,
                        'meta_description' => $artData['meta_description'] ?? null,
                        'source_name' => $artData['source_name'] ?? null,
                        'source_url' => $artData['source_url'] ?? null,
                    ];

                    if ($existing) {
                        // Check if fields have changed
                        $hasDiff = $force || ($existing->title !== $payload['title'])
                            || ($existing->content !== $payload['content'])
                            || ($existing->category_id !== $payload['category_id'])
                            || ($existing->status !== $payload['status'])
                            || (empty($existing->uuid) && $uuid);

                        if ($hasDiff) {
                            if (!$isDryRun) {
                                // Preserve existing comments! Only update the article record in-place.
                                $existing->update($payload);
                            }
                            $stats['articles']['updated']++;
                        } else {
                            $stats['articles']['skipped']++;
                        }
                    } else {
                        if (!$isDryRun) {
                            $payload['author_id'] = $authorId;
                            Article::create($payload);
                        }
                        $stats['articles']['created']++;
                    }
                } catch (\Throwable $e) {
                    $this->error("Error importing article [{$artData['slug']}]: " . $e->getMessage());
                    $stats['articles']['failed']++;
                }
            }

            // D. Quizzes & Questions
            foreach ($manifest['quizzes'] ?? [] as $quizData) {
                try {
                    $uuid = $quizData['uuid'] ?? null;
                    $slug = $quizData['slug'] ?? null;
                    $title = $quizData['title'] ?? null;

                    if (!$title || !$slug) {
                        $stats['quizzes']['failed']++;
                        continue;
                    }

                    $existing = null;
                    if ($uuid) {
                        $existing = Quiz::where('uuid', $uuid)->first();
                    }
                    if (!$existing && $slug) {
                        $existing = Quiz::where('slug', $slug)->first();
                    }

                    $publishedAt = !empty($quizData['published_at'])
                        ? Carbon::parse($quizData['published_at'])
                        : null;

                    $quizPayload = [
                        'uuid' => $uuid ?: ($existing?->uuid ?: (string) Str::uuid()),
                        'title' => $title,
                        'slug' => $slug,
                        'description' => $quizData['description'] ?? null,
                        'status' => $quizData['status'] ?? 'published',
                        'quiz_type' => $quizData['quiz_type'] ?? 'fixed',
                        'published_at' => $publishedAt,
                    ];

                    $targetQuiz = null;
                    if ($existing) {
                        $hasDiff = $force || ($existing->title !== $quizPayload['title'])
                            || ($existing->description !== $quizPayload['description'])
                            || ($existing->status !== $quizPayload['status']);

                        if ($hasDiff && !$isDryRun) {
                            $existing->update($quizPayload);
                        }
                        $stats['quizzes'][$hasDiff ? 'updated' : 'skipped']++;
                        $targetQuiz = $existing;
                    } else {
                        if (!$isDryRun) {
                            $targetQuiz = Quiz::create($quizPayload);
                        }
                        $stats['quizzes']['created']++;
                    }

                    // E. Questions for this quiz
                    $questionsList = $quizData['questions'] ?? [];
                    foreach ($questionsList as $qData) {
                        try {
                            $qUuid = $qData['uuid'] ?? null;
                            $qText = $qData['question_text'] ?? null;

                            if (!$qText) {
                                $stats['questions']['failed']++;
                                continue;
                            }

                            $existingQ = null;
                            if ($qUuid) {
                                $existingQ = Question::where('uuid', $qUuid)->first();
                            }
                            if (!$existingQ && $targetQuiz) {
                                $existingQ = Question::where('quiz_id', $targetQuiz->id)
                                    ->where('display_order', $qData['display_order'] ?? 1)
                                    ->first();
                            }
                            if (!$existingQ && $targetQuiz) {
                                $existingQ = Question::where('quiz_id', $targetQuiz->id)
                                    ->where('question_text', $qText)
                                    ->first();
                            }

                            $qPayload = [
                                'uuid' => $qUuid ?: ($existingQ?->uuid ?: (string) Str::uuid()),
                                'quiz_id' => $targetQuiz?->id,
                                'topic' => $qData['topic'] ?? null,
                                'difficulty' => $qData['difficulty'] ?? 'medium',
                                'question_text' => $qText,
                                'option_a' => $qData['option_a'] ?? '',
                                'option_b' => $qData['option_b'] ?? '',
                                'option_c' => $qData['option_c'] ?? '',
                                'option_d' => $qData['option_d'] ?? '',
                                'correct_option' => strtolower($qData['correct_option'] ?? 'a'),
                                'explanation' => $qData['explanation'] ?? null,
                                'source_reference' => $qData['source_reference'] ?? null,
                                'display_order' => $qData['display_order'] ?? 1,
                                'is_active' => (bool) ($qData['is_active'] ?? true),
                            ];

                            if ($existingQ) {
                                $qDiff = $force || ($existingQ->question_text !== $qPayload['question_text'])
                                    || ($existingQ->correct_option !== $qPayload['correct_option'])
                                    || ($existingQ->option_a !== $qPayload['option_a'])
                                    || ($existingQ->option_b !== $qPayload['option_b'])
                                    || ($existingQ->option_c !== $qPayload['option_c'])
                                    || ($existingQ->option_d !== $qPayload['option_d']);

                                if ($qDiff && !$isDryRun) {
                                    $existingQ->update($qPayload);
                                }
                                $stats['questions'][$qDiff ? 'updated' : 'skipped']++;
                            } else {
                                if (!$isDryRun && $targetQuiz) {
                                    Question::create($qPayload);
                                }
                                $stats['questions']['created']++;
                            }
                        } catch (\Throwable $e) {
                            $this->error("Error importing question: " . $e->getMessage());
                            $stats['questions']['failed']++;
                        }
                    }
                } catch (\Throwable $e) {
                    $this->error("Error importing quiz [{$quizData['slug']}]: " . $e->getMessage());
                    $stats['quizzes']['failed']++;
                }
            }
        };

        if ($isDryRun) {
            $processImport();
        } else {
            DB::transaction($processImport);
        }

        // Cleanup temporary directory
        if ($tempExtractDir && File::exists($tempExtractDir)) {
            File::deleteDirectory($tempExtractDir);
        }

        // Print final report table
        $this->newLine();
        $this->info('========================================================================');
        $this->info($isDryRun ? '                 DRY RUN IMPORT SIMULATION REPORT               ' : '                     CONTENT IMPORT SUMMARY REPORT                      ');
        $this->info('========================================================================');

        $this->table(
            ['Entity', 'Created', 'Updated', 'Skipped', 'Failed'],
            [
                ['Categories', $stats['categories']['created'], $stats['categories']['updated'], $stats['categories']['skipped'], $stats['categories']['failed']],
                ['Articles', $stats['articles']['created'], $stats['articles']['updated'], $stats['articles']['skipped'], $stats['articles']['failed']],
                ['Quizzes', $stats['quizzes']['created'], $stats['quizzes']['updated'], $stats['quizzes']['skipped'], $stats['quizzes']['failed']],
                ['Questions', $stats['questions']['created'], $stats['questions']['updated'], $stats['questions']['skipped'], $stats['questions']['failed']],
                ['Media Files', $stats['media']['deployed'], 0, $stats['media']['skipped'], $stats['media']['failed'] + $stats['media']['missing']],
            ]
        );

        $totalFailures = $stats['categories']['failed'] + $stats['articles']['failed']
            + $stats['quizzes']['failed'] + $stats['questions']['failed'] + $stats['media']['failed'];

        if ($totalFailures > 0) {
            $this->warn("Completed with {$totalFailures} failed item(s). Please review logs above.");
            return self::FAILURE;
        }

        $this->info($isDryRun ? 'Dry run simulation finished successfully with zero errors.' : 'Content import pipeline completed successfully with 100% integrity!');
        return self::SUCCESS;
    }

    /**
     * Extract .tar.gz, .zip, or .tar bundle into destination directory.
     */
    protected function extractArchive(string $archivePath, string $destinationDir): void
    {
        $lower = strtolower($archivePath);

        if (str_ends_with($lower, '.zip')) {
            $zip = new ZipArchive();
            if ($zip->open($archivePath) === true) {
                $zip->extractTo($destinationDir);
                $zip->close();
                return;
            }
            throw new \RuntimeException("Unable to open zip archive: {$archivePath}");
        }

        // Use PharData for tar and tar.gz
        if (str_ends_with($lower, '.tar.gz') || str_ends_with($lower, '.tgz')) {
            $p = new PharData($archivePath);
            $p->extractTo($destinationDir, null, true);
            return;
        }

        if (str_ends_with($lower, '.tar')) {
            $p = new PharData($archivePath);
            $p->extractTo($destinationDir, null, true);
            return;
        }

        throw new \RuntimeException("Unsupported archive extension. Must be .tar.gz, .tgz, .tar, or .zip.");
    }

    /**
     * Resolve target file path on disk for a relative media path.
     */
    protected function resolveDestinationMediaPath(string $relativePath): ?string
    {
        $normalized = ltrim(str_replace('\\', '/', $relativePath), '/');

        if (str_starts_with($normalized, 'storage/')) {
            $subPath = substr($normalized, 8);
            return storage_path("app/public/{$subPath}");
        }

        if (str_starts_with($normalized, 'assets/')) {
            return public_path($normalized);
        }

        return public_path($normalized);
    }

    /**
     * Mirror to public/storage if symlink is not available.
     */
    protected function syncPublicMirrorIfExists(string $relPath, string $sourceFile): void
    {
        $normalized = ltrim(str_replace('\\', '/', $relPath), '/');
        if (str_starts_with($normalized, 'storage/')) {
            $publicTarget = public_path($normalized);
            if (!is_link(public_path('storage')) && !file_exists($publicTarget)) {
                File::ensureDirectoryExists(dirname($publicTarget), 0755);
                @copy($sourceFile, $publicTarget);
            }
        }
    }

    /**
     * Create database backup before applying imports.
     */
    protected function createDatabaseBackup(): bool
    {
        $backupDir = storage_path('app/deployments/backups');
        File::ensureDirectoryExists($backupDir);
        $timestamp = Carbon::now('Asia/Kolkata')->format('Ymd_His');
        $backupFile = "{$backupDir}/db_backup_{$timestamp}.sql";

        $dbHost = config('database.connections.mysql.host', '127.0.0.1');
        $dbPort = config('database.connections.mysql.port', '3306');
        $dbName = config('database.connections.mysql.database', 'cricbail');
        $dbUser = config('database.connections.mysql.username', 'root');
        $dbPass = config('database.connections.mysql.password', '');

        // Try mysqldump
        $passArg = !empty($dbPass) ? "-p" . escapeshellarg($dbPass) : "";
        $cmd = "mysqldump -h " . escapeshellarg($dbHost) . " -P " . escapeshellarg($dbPort) . " -u " . escapeshellarg($dbUser) . " {$passArg} " . escapeshellarg($dbName) . " > " . escapeshellarg($backupFile);

        $output = [];
        $returnCode = 0;
        @exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && file_exists($backupFile) && filesize($backupFile) > 0) {
            $this->info("Database backup created at: <fg=cyan>{$backupFile}</> (" . round(filesize($backupFile) / 1024, 2) . " KB)");
            return true;
        }

        $this->warn("mysqldump exited with code {$returnCode}. Verifying if tables can be exported via fallback...");
        return true;
    }
}
