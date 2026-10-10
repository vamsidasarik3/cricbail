<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Category;
use App\Models\Question;
use App\Models\Quiz;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Phar;
use PharData;
use ZipArchive;

class ExportContentForDeploy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'content:export-for-deploy
                            {--output= : Custom path for export bundle archive}
                            {--include-drafts : Include draft articles and quizzes}
                            {--format=tar : Archive format: tar (tar.gz) or zip}
                            {--manifest-only : Only generate manifest JSON without media packaging}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export CricBail content (articles, categories, quizzes, questions, media) for production deployment';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('========================================================================');
        $this->info('           CRICBAIL: CONTENT EXPORT FOR PRODUCTION DEPLOYMENT           ');
        $this->info('========================================================================');

        $includeDrafts = $this->option('include-drafts');
        $format = strtolower($this->option('format') ?: 'tar');
        $manifestOnly = $this->option('manifest-only');

        $timestamp = Carbon::now('Asia/Kolkata')->format('Ymd_His');
        $defaultDir = storage_path('app/deployments/exports');
        File::ensureDirectoryExists($defaultDir);

        $outputPath = $this->option('output');
        if (!$outputPath) {
            $ext = ($format === 'zip') ? 'zip' : 'tar.gz';
            $outputPath = "{$defaultDir}/cricbail_content_{$timestamp}.{$ext}";
        }

        $tempDir = storage_path("app/deployments/tmp_export_{$timestamp}_" . Str::random(8));
        File::ensureDirectoryExists("{$tempDir}/media");

        try {
            $this->line("Exporting content items (Include Drafts: " . ($includeDrafts ? 'Yes' : 'No') . ")...");

            // 1. Categories
            $categories = Category::all()->map(function (Category $cat) {
                return [
                    'uuid' => $cat->uuid ?: (string) Str::uuid(),
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'description' => $cat->description,
                ];
            })->values()->toArray();

            // 2. Articles
            $articlesQuery = Article::with(['category', 'author']);
            if (!$includeDrafts) {
                $articlesQuery->where('status', 'published');
            }
            $articles = $articlesQuery->get();

            $mediaInventory = [];
            $exportedArticles = [];

            foreach ($articles as $article) {
                $featuredImage = $article->featured_image;

                if ($featuredImage && !str_contains($featuredImage, '..')) {
                    $mediaInventory[$featuredImage] = $this->resolveLocalMediaPath($featuredImage);
                }

                // Also inspect content for local images
                if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $article->content, $matches)) {
                    foreach ($matches[1] as $imgSrc) {
                        $cleaned = ltrim(parse_url($imgSrc, PHP_URL_PATH) ?? '', '/');
                        if (!empty($cleaned) && !str_contains($cleaned, '..')) {
                            if (str_starts_with($cleaned, 'storage/') || str_starts_with($cleaned, 'assets/')) {
                                $mediaInventory[$cleaned] = $this->resolveLocalMediaPath($cleaned);
                            }
                        }
                    }
                }

                $exportedArticles[] = [
                    'uuid' => $article->uuid ?: (string) Str::uuid(),
                    'title' => $article->title,
                    'slug' => $article->slug,
                    'category_uuid' => $article->category?->uuid,
                    'category_slug' => $article->category?->slug,
                    'category_name' => $article->category?->name,
                    'author_email' => $article->author?->email ?? 'admin@cricbail.com',
                    'author_name' => $article->author?->name ?? 'CricBail Editorial Team',
                    'excerpt' => $article->excerpt,
                    'content' => $article->content,
                    'featured_image' => $featuredImage,
                    'status' => $article->status,
                    'published_at' => $article->published_at?->format('Y-m-d H:i:s'),
                    'meta_title' => $article->meta_title,
                    'meta_description' => $article->meta_description,
                    'source_name' => $article->source_name,
                    'source_url' => $article->source_url,
                ];
            }

            // 3. Quizzes & Questions
            $quizzesQuery = Quiz::with('questions');
            if (!$includeDrafts) {
                $quizzesQuery->where('status', 'published');
            }
            $quizzes = $quizzesQuery->get();

            $exportedQuizzes = [];
            $totalQuestions = 0;

            foreach ($quizzes as $quiz) {
                $questionsData = [];
                foreach ($quiz->questions as $q) {
                    $totalQuestions++;
                    $questionsData[] = [
                        'uuid' => $q->uuid ?: (string) Str::uuid(),
                        'topic' => $q->topic,
                        'difficulty' => $q->difficulty,
                        'question_text' => $q->question_text,
                        'option_a' => $q->option_a,
                        'option_b' => $q->option_b,
                        'option_c' => $q->option_c,
                        'option_d' => $q->option_d,
                        'correct_option' => $q->correct_option,
                        'explanation' => $q->explanation,
                        'source_reference' => $q->source_reference,
                        'display_order' => $q->display_order,
                        'is_active' => (bool) $q->is_active,
                    ];
                }

                $exportedQuizzes[] = [
                    'uuid' => $quiz->uuid ?: (string) Str::uuid(),
                    'title' => $quiz->title,
                    'slug' => $quiz->slug,
                    'description' => $quiz->description,
                    'status' => $quiz->status,
                    'quiz_type' => $quiz->quiz_type ?? 'fixed',
                    'published_at' => $quiz->published_at?->format('Y-m-d H:i:s'),
                    'questions' => $questionsData,
                ];
            }

            // 4. Package media files into tempDir/media
            $mediaManifest = [];
            $copiedMediaCount = 0;
            $missingMediaCount = 0;

            if (!$manifestOnly) {
                foreach ($mediaInventory as $relativePath => $absolutePath) {
                    if ($absolutePath && file_exists($absolutePath) && is_file($absolutePath)) {
                        $safeRelPath = ltrim($relativePath, '/\\');
                        $targetPath = "{$tempDir}/media/{$safeRelPath}";
                        File::ensureDirectoryExists(dirname($targetPath));
                        File::copy($absolutePath, $targetPath);

                        $mediaManifest[] = [
                            'relative_path' => $safeRelPath,
                            'bundle_path' => "media/{$safeRelPath}",
                            'size' => filesize($absolutePath),
                            'sha256' => hash_file('sha256', $absolutePath),
                        ];
                        $copiedMediaCount++;
                    } else {
                        $missingMediaCount++;
                        $this->warn("Referenced media not found locally: {$relativePath}");
                    }
                }
            }

            // 5. Build structured manifest
            $manifest = [
                'manifest_version' => '1.0',
                'generator' => 'CricBail Deployment Exporter',
                'exported_at' => Carbon::now('Asia/Kolkata')->toIso8601String(),
                'counts' => [
                    'categories' => count($categories),
                    'articles' => count($exportedArticles),
                    'quizzes' => count($exportedQuizzes),
                    'questions' => $totalQuestions,
                    'media_files' => count($mediaManifest),
                ],
                'categories' => $categories,
                'articles' => $exportedArticles,
                'quizzes' => $exportedQuizzes,
                'media_files' => $mediaManifest,
            ];

            file_put_contents(
                "{$tempDir}/manifest.json",
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            );

            // 6. Package bundle into final archive
            File::ensureDirectoryExists(dirname($outputPath));
            if (file_exists($outputPath)) {
                File::delete($outputPath);
            }

            if ($manifestOnly) {
                // If manifest-only, simply copy manifest.json to output
                $jsonOutput = preg_replace('/\.(tar\.gz|zip|tar)$/i', '.json', $outputPath);
                File::copy("{$tempDir}/manifest.json", $jsonOutput);
                $finalOutput = $jsonOutput;
            } elseif ($format === 'zip') {
                $this->createZipArchive($tempDir, $outputPath);
                $finalOutput = $outputPath;
            } else {
                $this->createTarGzArchive($tempDir, $outputPath);
                $finalOutput = $outputPath;
            }

            // Cleanup temp directory
            File::deleteDirectory($tempDir);

            // Display summary table
            $fileSize = file_exists($finalOutput) ? round(filesize($finalOutput) / 1024, 2) . ' KB' : 'N/A';

            $this->newLine();
            $this->table(
                ['Content Entity', 'Exported Count', 'Status'],
                [
                    ['Categories', count($categories), '<fg=green>SUCCESS</>'],
                    ['Articles', count($exportedArticles), '<fg=green>SUCCESS</>'],
                    ['Quizzes', count($exportedQuizzes), '<fg=green>SUCCESS</>'],
                    ['Questions', $totalQuestions, '<fg=green>SUCCESS</>'],
                    ['Packaged Media Files', $copiedMediaCount, $missingMediaCount > 0 ? "<fg=yellow>{$copiedMediaCount} (Missing: {$missingMediaCount})</>" : '<fg=green>SUCCESS</>'],
                ]
            );

            $this->newLine();
            $this->info("Content export bundle successfully created at:");
            $this->line("<fg=cyan>{$finalOutput}</> ({$fileSize})");
            $this->line("Export Timestamp: " . $manifest['exported_at']);
            $this->newLine();

            // Print output path for deploy.sh parsing if invoked via script
            $this->line("BUNDLE_PATH={$finalOutput}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
            $this->error("Export failed: " . $e->getMessage());
            $this->line($e->getTraceAsString());
            return self::FAILURE;
        }
    }

    /**
     * Resolve absolute path on local disk for a media file reference.
     */
    protected function resolveLocalMediaPath(string $relativePath): ?string
    {
        $normalized = ltrim(str_replace('\\', '/', $relativePath), '/');

        // Check if starts with storage/
        if (str_starts_with($normalized, 'storage/')) {
            $subPath = substr($normalized, 8); // remove 'storage/'
            $candidates = [
                storage_path("app/public/{$subPath}"),
                public_path("storage/{$subPath}"),
                public_path($normalized),
            ];
            foreach ($candidates as $cand) {
                if (file_exists($cand) && is_file($cand)) {
                    return $cand;
                }
            }
        }

        // Check in public/
        $publicCandidate = public_path($normalized);
        if (file_exists($publicCandidate) && is_file($publicCandidate)) {
            return $publicCandidate;
        }

        // Check in storage/app/public directly
        $storageCandidate = storage_path("app/public/{$normalized}");
        if (file_exists($storageCandidate) && is_file($storageCandidate)) {
            return $storageCandidate;
        }

        return null;
    }

    /**
     * Package directory into .tar.gz using PharData.
     */
    protected function createTarGzArchive(string $sourceDir, string $destinationTarGz): void
    {
        $baseTar = preg_replace('/\.gz$/i', '', $destinationTarGz);
        if (!str_ends_with(strtolower($baseTar), '.tar')) {
            $baseTar .= '.tar';
        }

        if (file_exists($baseTar)) {
            unlink($baseTar);
        }
        if (file_exists($destinationTarGz)) {
            unlink($destinationTarGz);
        }

        $phar = new PharData($baseTar);
        $phar->buildFromDirectory($sourceDir);

        if (str_ends_with(strtolower($destinationTarGz), '.tar.gz') || str_ends_with(strtolower($destinationTarGz), '.tgz')) {
            $phar->compress(Phar::GZ);
            if (file_exists($baseTar)) {
                unlink($baseTar);
            }
        }
    }

    /**
     * Package directory into .zip using ZipArchive.
     */
    protected function createZipArchive(string $sourceDir, string $destinationZip): void
    {
        $zip = new ZipArchive();
        if ($zip->open($destinationZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Unable to create zip file at: {$destinationZip}");
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen(realpath($sourceDir)) + 1);
                $zip->addFile($filePath, str_replace('\\', '/', $relativePath));
            }
        }

        $zip->close();
    }
}
