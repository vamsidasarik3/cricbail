<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Quiz;
use App\Services\SeoService;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __construct(
        protected SeoService $seo
    ) {}

    /**
     * Render dynamic standard XML sitemap of all published resources with accurate lastmod timestamps.
     */
    public function index(): Response
    {
        $articles = Article::published()
            ->with('category')
            ->where('slug', '!=', 'ipl-2026-auction-highlights')
            ->latest('published_at')
            ->get();

        $categories = Category::where('slug', '!=', 'ipl-2026')->get();
        $quizzes = Quiz::where('status', 'published')->latest('published_at')->get();

        // Accurate lastmod values based on actual content
        $latestArticleTime = $articles->first()?->published_at?->toIso8601String() ?? '2026-10-10T00:00:00+05:30';
        $latestQuizTime = $quizzes->first()?->published_at?->toIso8601String() ?? '2026-10-10T00:00:00+05:30';

        $staticPages = [
            [
                'url' => $this->seo->canonicalUrl('/'),
                'lastmod' => $latestArticleTime,
                'changefreq' => 'hourly',
                'priority' => '1.0',
            ],
            [
                'url' => $this->seo->canonicalUrl('/news'),
                'lastmod' => $latestArticleTime,
                'changefreq' => 'hourly',
                'priority' => '0.9',
            ],
            [
                'url' => $this->seo->canonicalUrl('/about'),
                'lastmod' => '2026-10-09T00:00:00+05:30',
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ],
            [
                'url' => $this->seo->canonicalUrl('/contact'),
                'lastmod' => '2026-10-09T00:00:00+05:30',
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ],
            [
                'url' => $this->seo->canonicalUrl('/privacy-policy'),
                'lastmod' => '2026-10-09T00:00:00+05:30',
                'changefreq' => 'yearly',
                'priority' => '0.4',
            ],
        ];

        $categoryEntries = $categories->map(function (Category $category) use ($articles) {
            $catLatest = $articles->firstWhere('category_id', $category->id);
            $lastmod = $catLatest?->published_at?->toIso8601String() 
                ?? ($category->updated_at ? $category->updated_at->toIso8601String() : '2026-10-09T00:00:00+05:30');

            return [
                'url' => $this->seo->canonicalUrl('/category/' . $category->slug),
                'lastmod' => $lastmod,
                'changefreq' => 'daily',
                'priority' => '0.8',
            ];
        });

        $articleEntries = $articles->map(function (Article $article) {
            $lastmod = ($article->updated_at ?: $article->published_at)->toIso8601String();
            $image = $article->featured_image ? $this->seo->socialImage($article->featured_image) : null;

            return [
                'url' => $this->seo->canonicalUrl('/news/' . $article->slug),
                'lastmod' => $lastmod,
                'changefreq' => 'weekly',
                'priority' => '0.7',
                'title' => $article->title,
                'image' => $image,
            ];
        });

        $quizHub = [
            'url' => $this->seo->canonicalUrl('/quizzes'),
            'lastmod' => $latestQuizTime,
            'changefreq' => 'daily',
            'priority' => '0.8',
        ];

        $quizEntries = $quizzes->map(function (Quiz $quiz) {
            return [
                'url' => $this->seo->canonicalUrl('/quizzes/' . $quiz->slug),
                'lastmod' => ($quiz->updated_at ?: $quiz->published_at ?: now())->toIso8601String(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        });

        $content = view('sitemaps.index', compact(
            'staticPages', 
            'categoryEntries', 
            'articleEntries', 
            'quizHub', 
            'quizEntries'
        ))->render();

        return response($content, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }

    /**
     * Render rolling 48-hour Google News XML sitemap.
     */
    public function news(): Response
    {
        // Articles published within the past 48 hours per Google News specifications
        $recentNews = Article::published()
            ->where('slug', '!=', 'ipl-2026-auction-highlights')
            ->where('published_at', '>=', now()->subHours(48))
            ->latest('published_at')
            ->get();

        $newsEntries = $recentNews->map(function (Article $article) {
            return [
                'loc' => $this->seo->canonicalUrl('/news/' . $article->slug),
                'title' => $article->title,
                'published_at' => $article->published_at->toIso8601String(),
                'publication_name' => config('seo.site_name', 'CricBail'),
                'language' => 'en',
            ];
        });

        $content = view('sitemaps.news', compact('newsEntries'))->render();

        return response($content, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }

    /**
     * Render dynamic production robots.txt with validated HTTPS sitemap links.
     */
    public function robots(): Response
    {
        $sitemapUrl = $this->seo->canonicalUrl('/sitemap.xml');
        $newsSitemapUrl = $this->seo->canonicalUrl('/news-sitemap.xml');

        $content = implode("\n", [
            'User-agent: *',
            'Disallow: /admin/',
            'Disallow: /quizzes/attempt/',
            'Disallow: /quizzes/*/play',
            'Disallow: /quizzes/*/result/',
            'Allow: /assets/',
            '',
            'Sitemap: ' . $sitemapUrl,
            'Sitemap: ' . $newsSitemapUrl,
            '',
        ]);

        return response($content, 200)
            ->header('Content-Type', 'text/plain; charset=utf-8');
    }
}
