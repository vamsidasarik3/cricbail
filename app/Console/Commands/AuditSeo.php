<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Category;
use App\Models\Quiz;
use App\Services\SeoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AuditSeo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seo:audit 
                            {--json : Output the audit findings as a machine-readable JSON document}
                            {--output-file= : Save the JSON audit report to a specified file path}
                            {--fail-on-critical : Terminate with non-zero exit code if critical errors are found}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform an automated SEO and content health audit across the database, templates, and sitemaps.';

    /**
     * Audit findings accumulator.
     */
    protected array $findings = [];

    /**
     * Counts by severity.
     */
    protected array $severityCounts = [
        'CRITICAL' => 0,
        'WARNING' => 0,
        'INFO' => 0,
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isJson = $this->option('json');

        if (!$isJson) {
            $this->info('========================================================================');
            $this->info('         CRICBAIL AUTOMATED SEO & TECHNICAL CONTENT AUDIT               ');
            $this->info('========================================================================');
            $this->line('Starting audit inspection across database, configuration, and sitemaps...');
        }

        // 1. Audit SEO Configuration
        $this->auditConfiguration();

        // 2. Audit Categories
        $this->auditCategories();

        // 3. Audit Articles
        $this->auditArticles();

        // 4. Audit Quizzes
        $this->auditQuizzes();

        // 5. Audit Sitemaps and Robots
        $this->auditSitemapsAndRobots();

        // Output results
        if ($isJson) {
            $report = $this->buildJsonReport();
            $jsonOutput = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            
            $outputFile = $this->option('output-file');
            if ($outputFile) {
                File::put($outputFile, $jsonOutput);
            }
            $this->line($jsonOutput);
        } else {
            $this->renderCliSummary();
        }

        if ($this->option('fail-on-critical') && $this->severityCounts['CRITICAL'] > 0) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Audit application SEO configuration and asset files.
     */
    protected function auditConfiguration(): void
    {
        $domain = config('seo.canonical_domain');

        if (empty($domain)) {
            $this->addFinding('CRITICAL', 'Config', 'Canonical Domain Missing', 'config/seo.php has no canonical_domain configured.');
        } elseif (!str_starts_with($domain, 'https://')) {
            $this->addFinding('CRITICAL', 'Config', 'Insecure Canonical Domain', "canonical_domain must use HTTPS scheme. Found: '{$domain}'");
        } elseif (str_contains($domain, '8084') || str_contains($domain, 'localhost')) {
            $this->addFinding('CRITICAL', 'Config', 'Local Development Host Leaked', "canonical_domain points to local development environment: '{$domain}'");
        }

        $siteName = config('seo.site_name');
        if (empty($siteName)) {
            $this->addFinding('WARNING', 'Config', 'Site Name Missing', 'config/seo.php has no site_name configured.');
        }

        $defaultDesc = config('seo.default_description');
        if (empty($defaultDesc)) {
            $this->addFinding('WARNING', 'Config', 'Default Description Missing', 'config/seo.php has no default_description configured.');
        } elseif (strlen($defaultDesc) < 100 || strlen($defaultDesc) > 165) {
            $this->addFinding('INFO', 'Config', 'Default Description Length', 'default_description should ideally be between 120 and 160 characters (current: ' . strlen($defaultDesc) . ').');
        }

        // Verify social card fallback image
        $defaultImg = config('seo.default_image', 'assets/images/cricket/cricbail_share_card.jpg');
        $fullPath = public_path($defaultImg);
        if (!File::exists($fullPath)) {
            $this->addFinding('CRITICAL', 'Config', 'Missing Fallback Social Card', "The default social image at '{$defaultImg}' does not exist on disk.");
        } elseif (str_ends_with(strtolower($defaultImg), '.svg')) {
            $this->addFinding('CRITICAL', 'Config', 'Invalid Fallback Image Format', "Social cards must be JPEG/PNG/WebP. SVG is unsupported by search engine and social crawlers.");
        }

        // Check robots.txt
        $robotsPath = public_path('robots.txt');
        if (!File::exists($robotsPath)) {
            $this->addFinding('WARNING', 'Robots', 'Missing Static robots.txt', "public/robots.txt file was not found on disk.");
        } else {
            $robotsContent = File::get($robotsPath);
            if (!str_contains($robotsContent, 'Disallow: /admin/')) {
                $this->addFinding('CRITICAL', 'Robots', 'Admin Portal Exposed', "robots.txt does not disallow the /admin/ path.");
            }
            if (!str_contains($robotsContent, 'Sitemap:')) {
                $this->addFinding('WARNING', 'Robots', 'Sitemap Reference Missing', "robots.txt does not declare the Sitemap index directive.");
            }
            if (str_contains($robotsContent, '8084')) {
                $this->addFinding('CRITICAL', 'Robots', 'Local Port in robots.txt', "robots.txt references local development port 8084.");
            }
        }
    }

    /**
     * Audit category records.
     */
    protected function auditCategories(): void
    {
        $categories = Category::all();
        $slugs = [];

        foreach ($categories as $cat) {
            if (empty(trim($cat->name))) {
                $this->addFinding('CRITICAL', 'Category', 'Empty Category Name', "Category #{$cat->id} has an empty name.");
            }

            if (empty(trim($cat->slug))) {
                $this->addFinding('CRITICAL', 'Category', 'Empty Category Slug', "Category '{$cat->name}' has an empty URL slug.");
            } elseif (in_array($cat->slug, $slugs)) {
                $this->addFinding('CRITICAL', 'Category', 'Duplicate Category Slug', "Category slug '{$cat->slug}' is duplicated.");
            } else {
                $slugs[] = $cat->slug;
            }

            // Check if category has published articles
            $publishedCount = $cat->articles()->published()->count();
            if ($publishedCount === 0) {
                $this->addFinding('INFO', 'Category', 'Empty Category', "Category '{$cat->name}' (/category/{$cat->slug}) has 0 published articles.");
            }
        }
    }

    /**
     * Audit published articles.
     */
    protected function auditArticles(): void
    {
        $articles = Article::with(['category', 'author'])->get();
        $slugs = [];

        foreach ($articles as $article) {
            $isPublished = ($article->status === 'published');

            // Check publication timestamp consistency
            if ($isPublished && empty($article->published_at)) {
                $this->addFinding('CRITICAL', 'Article', 'Missing Published Date', "Published article #{$article->id} ('{$article->title}') has null published_at timestamp.", $article->id);
            }

            if ($isPublished && $article->published_at && $article->published_at->isFuture()) {
                $this->addFinding('INFO', 'Article', 'Scheduled Future Publication', "Article #{$article->id} has future published_at date ({$article->published_at->toIso8601String()}).", $article->id);
            }

            // Slug checks
            if (empty(trim($article->slug))) {
                $this->addFinding('CRITICAL', 'Article', 'Empty Slug', "Article #{$article->id} has an empty URL slug.", $article->id);
            } else {
                if (in_array($article->slug, $slugs)) {
                    $this->addFinding('CRITICAL', 'Article', 'Duplicate Slug', "Duplicate article slug detected: '{$article->slug}'.", $article->id);
                } else {
                    $slugs[] = $article->slug;
                }

                if ($article->slug !== Str::slug($article->slug)) {
                    $this->addFinding('WARNING', 'Article', 'Non-Canonical Slug Format', "Slug '{$article->slug}' contains non-standard characters.", $article->id);
                }
            }

            // Check relationships
            if (!$article->category) {
                $this->addFinding('CRITICAL', 'Article', 'Orphaned Category Reference', "Article #{$article->id} references missing or null category (category_id: {$article->category_id}).", $article->id);
            }

            if (!$article->author) {
                $this->addFinding('CRITICAL', 'Article', 'Missing Author Relationship', "Article #{$article->id} has no valid author record (author_id: {$article->author_id}).", $article->id);
            }

            // Editorial metadata checks for published stories
            if ($isPublished) {
                if (empty(trim($article->title))) {
                    $this->addFinding('CRITICAL', 'Article', 'Missing Headline', "Article #{$article->id} has an empty headline.", $article->id);
                }

                $metaTitle = $article->meta_title ?: $article->title;
                if (str_contains($metaTitle, '| CricBail | CricBail')) {
                    $this->addFinding('WARNING', 'Article', 'Duplicate Brand Suffix in Stored Title', "Article #{$article->id} stored meta_title contains repeated brand suffix.", $article->id);
                }

                $metaDesc = $article->meta_description ?: $article->excerpt;
                if (empty($metaDesc)) {
                    $this->addFinding('WARNING', 'Article', 'Missing Meta Description', "Article #{$article->id} ('{$article->title}') has neither meta_description nor excerpt.", $article->id);
                } elseif (strlen($metaDesc) < 60) {
                    $this->addFinding('WARNING', 'Article', 'Short Meta Description', "Article #{$article->id} meta description is very short (" . strlen($metaDesc) . " chars, aim for 120-160).", $article->id);
                }

                // Featured image validation
                if (empty($article->featured_image)) {
                    $this->addFinding('WARNING', 'Article', 'Missing Featured Image', "Article #{$article->id} has no featured_image assigned.", $article->id);
                } else {
                    $imgPath = public_path($article->featured_image);
                    if (!File::exists($imgPath)) {
                        $this->addFinding('WARNING', 'Article', 'Missing Image File on Disk', "Featured image '{$article->featured_image}' for article #{$article->id} was not found on disk.", $article->id);
                    }
                }

                // Thin content check
                $plainText = strip_tags($article->content ?? '');
                $wordCount = str_word_count($plainText);
                if ($wordCount < 150) {
                    $this->addFinding('WARNING', 'Article', 'Thin Content Warning', "Article #{$article->id} contains only {$wordCount} words (recommend 300+ for substantive reporting).", $article->id);
                }
            }
        }
    }

    /**
     * Audit published quizzes.
     */
    protected function auditQuizzes(): void
    {
        $quizzes = Quiz::with('questions')->get();
        $quizSlugs = [];

        foreach ($quizzes as $quiz) {
            $isPublished = ($quiz->status === 'published');

            if (empty(trim($quiz->slug))) {
                $this->addFinding('CRITICAL', 'Quiz', 'Empty Quiz Slug', "Quiz #{$quiz->id} has an empty URL slug.", $quiz->id);
            } elseif (in_array($quiz->slug, $quizSlugs)) {
                $this->addFinding('CRITICAL', 'Quiz', 'Duplicate Quiz Slug', "Duplicate quiz slug detected: '{$quiz->slug}'.", $quiz->id);
            } else {
                $quizSlugs[] = $quiz->slug;
            }

            if ($isPublished) {
                if (empty(trim($quiz->title))) {
                    $this->addFinding('CRITICAL', 'Quiz', 'Missing Quiz Title', "Published quiz #{$quiz->id} has an empty title.", $quiz->id);
                }

                if (empty(trim($quiz->description))) {
                    $this->addFinding('WARNING', 'Quiz', 'Missing Quiz Description', "Published quiz #{$quiz->id} has no description.", $quiz->id);
                }

                if ($quiz->questions->count() !== Quiz::REQUIRED_QUESTION_COUNT) {
                    $this->addFinding('CRITICAL', 'Quiz', 'Invalid Question Count', "Published quiz #{$quiz->id} has {$quiz->questions->count()} questions (must have exactly " . Quiz::REQUIRED_QUESTION_COUNT . ").", $quiz->id);
                }

                if (empty($quiz->published_at)) {
                    $this->addFinding('CRITICAL', 'Quiz', 'Missing Published Date', "Published quiz #{$quiz->id} has null published_at.", $quiz->id);
                }
            }
        }
    }

    /**
     * Audit dynamic sitemaps and indexing hygiene.
     */
    protected function auditSitemapsAndRobots(): void
    {
        $canonicalDomain = config('seo.canonical_domain', 'https://www.cricbail.com');

        // Check standard sitemap generator
        try {
            $sitemapController = app(\App\Http\Controllers\SitemapController::class);
            $response = $sitemapController->index();
            $content = $response->getContent();

            if (!str_contains($content, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"')) {
                $this->addFinding('CRITICAL', 'Sitemap', 'Invalid Sitemap XML', "Sitemap output does not contain valid sitemap XML namespace.");
            }

            if (str_contains($content, '8084') || str_contains($content, 'localhost')) {
                $this->addFinding('CRITICAL', 'Sitemap', 'Development Host Leaked in Sitemap', "Sitemap output contains local development URL.");
            }

            if (str_contains($content, '/admin')) {
                $this->addFinding('CRITICAL', 'Sitemap', 'Admin Portal Leaked in Sitemap', "Sitemap accidentally contains /admin URLs.");
            }

            if (str_contains($content, '/quizzes/*/play') || str_contains($content, '/result/')) {
                $this->addFinding('CRITICAL', 'Sitemap', 'Private Attempt URL in Sitemap', "Sitemap contains private quiz attempt endpoints.");
            }

            // Verify drafts are excluded
            $draftSlugs = Article::where('status', 'draft')->pluck('slug')->toArray();
            foreach ($draftSlugs as $dSlug) {
                if (!empty($dSlug) && str_contains($content, "/news/{$dSlug}")) {
                    $this->addFinding('CRITICAL', 'Sitemap', 'Draft Article in Sitemap', "Draft article '{$dSlug}' is present in the public sitemap!");
                }
            }
        } catch (\Throwable $e) {
            $this->addFinding('CRITICAL', 'Sitemap', 'Sitemap Generation Error', "Exception rendering sitemap: " . $e->getMessage());
        }

        // Check Google News sitemap
        try {
            $newsResponse = app(\App\Http\Controllers\SitemapController::class)->news();
            $newsContent = $newsResponse->getContent();

            if (!str_contains($newsContent, 'xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"')) {
                $this->addFinding('CRITICAL', 'NewsSitemap', 'Invalid News Sitemap XML', "News sitemap does not declare google news namespace.");
            }

            // Verify rolling 48-hour inclusion: no article published > 48h ago
            $oldArticleSlugs = Article::published()
                ->where('published_at', '<', now()->subHours(48))
                ->pluck('slug')
                ->toArray();

            foreach ($oldArticleSlugs as $oldSlug) {
                if (str_contains($newsContent, "/news/{$oldSlug}")) {
                    $this->addFinding('WARNING', 'NewsSitemap', 'Article Exceeds 48h Window', "News sitemap includes article older than 48 hours: '{$oldSlug}'.");
                    break;
                }
            }
        } catch (\Throwable $e) {
            $this->addFinding('CRITICAL', 'NewsSitemap', 'News Sitemap Error', "Exception rendering news sitemap: " . $e->getMessage());
        }
    }

    /**
     * Record a finding.
     */
    protected function addFinding(string $severity, string $category, string $title, string $details, ?int $entityId = null): void
    {
        $severity = strtoupper($severity);
        $this->severityCounts[$severity] = ($this->severityCounts[$severity] ?? 0) + 1;

        $this->findings[] = [
            'severity' => $severity,
            'category' => $category,
            'title' => $title,
            'details' => $details,
            'entity_id' => $entityId,
        ];
    }

    /**
     * Render a clean human-readable CLI summary.
     */
    protected function renderCliSummary(): void
    {
        $this->newLine();
        $this->info("AUDIT FINDINGS SUMMARY:");
        $this->line("------------------------------------------------------------------------");
        $this->line("CRITICAL Issues : <fg=red>{$this->severityCounts['CRITICAL']}</>");
        $this->line("WARNINGS        : <fg=yellow>{$this->severityCounts['WARNING']}</>");
        $this->line("ADVISORY INFO   : <fg=cyan>{$this->severityCounts['INFO']}</>");
        $this->line("------------------------------------------------------------------------");

        if (empty($this->findings)) {
            $this->newLine();
            $this->info("✓ PERFECT HEALTH: No SEO, technical, or metadata defects detected!");
            return;
        }

        $tableData = [];
        foreach ($this->findings as $f) {
            $color = match ($f['severity']) {
                'CRITICAL' => 'red',
                'WARNING' => 'yellow',
                default => 'cyan',
            };

            $tableData[] = [
                "<fg={$color}>{$f['severity']}</>",
                $f['category'],
                $f['title'],
                $f['details'],
            ];
        }

        $this->table(['Severity', 'Category', 'Issue', 'Details'], $tableData);

        $this->newLine();
        if ($this->severityCounts['CRITICAL'] > 0) {
            $this->error("CRITICAL ISSUES DETECTED: Address these items prior to production release.");
        } else {
            $this->info("✓ AUDIT PASSED: Zero critical issues detected. Only editorial warnings/advisories remain.");
        }
    }

    /**
     * Generate structured JSON output.
     */
    protected function buildJsonReport(): array
    {
        return [
            'audit_timestamp' => now()->toIso8601String(),
            'canonical_domain' => config('seo.canonical_domain'),
            'summary' => [
                'total_issues' => count($this->findings),
                'critical_count' => $this->severityCounts['CRITICAL'],
                'warning_count' => $this->severityCounts['WARNING'],
                'info_count' => $this->severityCounts['INFO'],
                'status' => $this->severityCounts['CRITICAL'] === 0 ? 'PASSED' : 'FAILED',
            ],
            'statistics' => [
                'total_articles' => Article::count(),
                'published_articles' => Article::published()->count(),
                'draft_articles' => Article::where('status', 'draft')->count(),
                'total_categories' => Category::count(),
                'total_quizzes' => Quiz::count(),
                'published_quizzes' => Quiz::published()->count(),
            ],
            'findings' => $this->findings,
        ];
    }
}
