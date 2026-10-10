<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    /**
     * Test homepage SEO metadata and structured data.
     */
    public function test_homepage_seo_metadata_and_structured_data(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        // Single brand suffix
        $response->assertSee('<title>CricBail - Modern Cricket Journalism & Tactical Match Analysis</title>', false);
        $this->assertStringNotContainsString('| CricBail | CricBail', $response->getContent());

        // Canonical URL
        $canonicalDomain = config('seo.canonical_domain', 'https://www.cricbail.com');
        $response->assertSee('<link rel="canonical" href="' . $canonicalDomain . '">', false);

        // JSON-LD WebSite and Organization
        $content = $response->getContent();
        $this->assertStringContainsString('"@type": "WebSite"', $content);
        $this->assertStringContainsString('"@type": "Organization"', $content);
        $this->assertStringContainsString('"name": "CricBail"', $content);
    }

    /**
     * Test article SEO metadata, single title suffix, Open Graph, and NewsArticle JSON-LD schema.
     */
    public function test_article_seo_metadata_and_newsarticle_schema(): void
    {
        $article = Article::published()->first();
        if (!$article) {
            $this->markTestSkipped('No published articles available for SEO test.');
        }

        $response = $this->get('/news/' . $article->slug);

        $response->assertStatus(200);

        // Verify single title suffix
        $content = $response->getContent();
        $this->assertStringNotContainsString('| CricBail | CricBail', $content);

        // Verify production canonical URL
        $canonicalDomain = config('seo.canonical_domain', 'https://www.cricbail.com');
        $expectedCanonical = $canonicalDomain . '/news/' . $article->slug;
        $response->assertSee('<link rel="canonical" href="' . $expectedCanonical . '">', false);

        // Verify Open Graph & Twitter Cards
        $response->assertSee('<meta property="og:type" content="article">', false);
        $response->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
        $response->assertSee('<meta property="og:url" content="' . $expectedCanonical . '">', false);

        // Verify NewsArticle Schema
        $this->assertStringContainsString('"@type": "NewsArticle"', $content);
        $this->assertStringContainsString('"headline":', $content);
        $this->assertStringContainsString('"datePublished":', $content);
        $this->assertStringContainsString('"dateModified":', $content);
        $this->assertStringContainsString('"author":', $content);
        $this->assertStringContainsString('"publisher":', $content);

        // Verify Breadcrumb Schema
        $this->assertStringContainsString('"@type": "BreadcrumbList"', $content);
    }

    /**
     * Test canonical URLs always use the configured production HTTPS domain regardless of local host header.
     */
    public function test_canonical_urls_always_use_production_domain(): void
    {
        $response = $this->withServerVariables(['HTTP_HOST' => 'cricbail.vkrshna.in:8084'])->get('/about');

        $response->assertStatus(200);

        $canonicalDomain = config('seo.canonical_domain', 'https://www.cricbail.com');
        $response->assertSee('<link rel="canonical" href="' . $canonicalDomain . '/about">', false);
        $this->assertStringNotContainsString('http://cricbail.vkrshna.in:8084/about', $response->getContent());
    }

    /**
     * Test category page SEO and breadcrumb schema.
     */
    public function test_category_page_seo_and_breadcrumbs(): void
    {
        $category = Category::where('slug', 'ipl')->first() ?? Category::first();
        if (!$category) {
            $this->markTestSkipped('No categories found.');
        }

        $response = $this->get('/category/' . $category->slug);

        $response->assertStatus(200);

        $canonicalDomain = config('seo.canonical_domain', 'https://www.cricbail.com');
        $response->assertSee('<link rel="canonical" href="' . $canonicalDomain . '/category/' . $category->slug . '">', false);

        $content = $response->getContent();
        $this->assertStringContainsString('"@type": "BreadcrumbList"', $content);
        $this->assertStringNotContainsString('| CricBail | CricBail', $content);
    }

    /**
     * Test quiz listing and quiz detail SEO and Quiz JSON-LD schema.
     */
    public function test_quiz_pages_seo_and_quiz_schema(): void
    {
        $response = $this->get('/quizzes');
        $response->assertStatus(200);
        $canonicalDomain = config('seo.canonical_domain', 'https://www.cricbail.com');
        $response->assertSee('<link rel="canonical" href="' . $canonicalDomain . '/quizzes">', false);

        $quiz = Quiz::published()->first();
        if ($quiz) {
            $showResponse = $this->get('/quizzes/' . $quiz->slug);
            $showResponse->assertStatus(200);
            $showResponse->assertSee('<link rel="canonical" href="' . $canonicalDomain . '/quizzes/' . $quiz->slug . '">', false);

            $showContent = $showResponse->getContent();
            $this->assertStringContainsString('"@type": "Quiz"', $showContent);
            $this->assertStringContainsString('"hasPart":', $showContent);
        }
    }

    /**
     * Test draft articles return 404 for guests and are excluded from public lists and sitemaps.
     */
    public function test_draft_articles_return_404_and_are_excluded(): void
    {
        $category = Category::first();
        $user = User::first();

        $draft = Article::create([
            'title' => 'Secret Upcoming Tour Draft Article',
            'slug' => 'secret-upcoming-tour-draft-' . uniqid(),
            'category_id' => $category->id,
            'author_id' => $user ? $user->id : 1,
            'content' => 'This is a private draft article that should not be visible to search engines or readers.',
            'status' => 'draft',
            'published_at' => null,
        ]);

        // Guest requesting draft should get 404
        $response = $this->get('/news/' . $draft->slug);
        $response->assertStatus(404);

        // Draft should not appear in sitemap
        $sitemapResponse = $this->get('/sitemap.xml');
        $this->assertStringNotContainsString($draft->slug, $sitemapResponse->getContent());

        // Cleanup test draft
        $draft->delete();
    }

    /**
     * Test dynamic XML sitemap outputs valid XML and contains published content with lastmod.
     */
    public function test_xml_sitemap_returns_valid_xml(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('Content-Type'), 'xml'));

        $content = $response->getContent();
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $content);
        $this->assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"', $content);
        $this->assertStringContainsString('<loc>' . config('seo.canonical_domain') . '</loc>', $content);
        $this->assertStringContainsString('<lastmod>', $content);

        // Excludes admin and private attempts
        $this->assertStringNotContainsString('/admin', $content);
        $this->assertStringNotContainsString('/attempt', $content);
    }

    /**
     * Test dynamic Google News sitemap rolling 48-hour logic.
     */
    public function test_google_news_sitemap_rolling_48_hour_window(): void
    {
        $response = $this->get('/news-sitemap.xml');

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('Content-Type'), 'xml'));

        $content = $response->getContent();
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $content);
        $this->assertStringContainsString('xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"', $content);
        $this->assertStringContainsString('<news:publication_date>', $content);
        $this->assertStringContainsString('<news:title>', $content);
    }

    /**
     * Test robots.txt directives and sitemap reference.
     */
    public function test_robots_txt_directives_and_sitemaps(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('Disallow: /admin/', $content);
        $this->assertStringContainsString('Disallow: /quizzes/attempt/', $content);
        $this->assertStringContainsString('Sitemap: ' . config('seo.canonical_domain') . '/sitemap.xml', $content);
        $this->assertStringContainsString('Sitemap: ' . config('seo.canonical_domain') . '/news-sitemap.xml', $content);
    }

    /**
     * Test 301 permanent redirects for legacy/duplicate URLs.
     */
    public function test_301_redirects_for_duplicate_and_legacy_urls(): void
    {
        // Legacy category
        $response = $this->get('/category/ipl-2026');
        $response->assertStatus(301);
        $response->assertRedirect('/category/ipl');

        // Legacy test article
        $responseArticle = $this->get('/news/ipl-2026-auction-highlights');
        $responseArticle->assertStatus(301);
        $responseArticle->assertRedirect('/category/ipl');

        // Query string category filter to canonical category URL
        $responseQuery = $this->get('/news?category=ipl');
        $responseQuery->assertStatus(301);
        $responseQuery->assertRedirect(route('news.category', 'ipl'));
    }

    /**
     * Test admin login page has noindex directive.
     */
    public function test_admin_login_has_noindex_directive(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }
}
