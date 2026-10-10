<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$consoleKernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$consoleKernel->bootstrap();

$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Models\Article;
use App\Models\Category;
use App\Models\Quiz;
use App\Models\User;
use App\Services\SeoService;
use Illuminate\Http\Request;

echo "=====================================================================" . PHP_EOL;
echo "  CRICBAIL: DYNAMIC SEO SYSTEM AUTOMATED VERIFICATION SUITE          " . PHP_EOL;
echo "=====================================================================" . PHP_EOL . PHP_EOL;

$failedTests = 0;
$totalTests = 0;

function assertCheck($condition, $description) {
    global $failedTests, $totalTests;
    $totalTests++;
    if ($condition) {
        echo "  [PASS] {$description}" . PHP_EOL;
    } else {
        echo "  [FAIL] {$description}" . PHP_EOL;
        $failedTests++;
    }
}

$canonicalDomain = config('seo.canonical_domain', 'https://www.cricbail.com');

// ---------------------------------------------------------------------
// TEST 1: CENTRALIZED SEO SERVICE & CANONICAL DOMAIN
// ---------------------------------------------------------------------
echo "--- 1. Testing SeoService Helpers & Canonical Configuration ---" . PHP_EOL;
assertCheck(str_starts_with($canonicalDomain, 'https://'), "Production domain uses secure HTTPS: {$canonicalDomain}");
assertCheck(!str_contains($canonicalDomain, '8084') && !str_contains($canonicalDomain, 'localhost'), "Canonical domain is strictly production, never local dev URL");

$cleanTitle1 = SeoService::title('IPL 2026 Auction Highlights | CricBail | CricBail');
assertCheck($cleanTitle1 === 'IPL 2026 Auction Highlights | CricBail', "SeoService strips double brand suffix correctly ('{$cleanTitle1}')");

$cleanTitle2 = SeoService::title('Test Cricket Analysis');
assertCheck($cleanTitle2 === 'Test Cricket Analysis | CricBail', "SeoService appends brand suffix when missing ('{$cleanTitle2}')");

$cleanDesc = SeoService::description('  <p>Leading analysis <b>story</b></p>  ');
assertCheck($cleanDesc === 'Leading analysis story', "SeoService cleans and strips tags from meta descriptions ('{$cleanDesc}')");

$socialImage = SeoService::socialImage('assets/images/cricket/stadium_floodlights.svg');
assertCheck(str_ends_with($socialImage, '.jpg'), "SeoService replaces unsupported SVG social cards with JPG card: {$socialImage}");

// ---------------------------------------------------------------------
// TEST 2: HOMEPAGE METADATA & STRUCTURED DATA
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 2. Testing Homepage SEO & WebSite Schema ---" . PHP_EOL;
$homeReq = Request::create('/', 'GET');
$homeRes = $httpKernel->handle($homeReq);
$homeContent = $homeRes->getContent();

assertCheck($homeRes->getStatusCode() === 200, "Homepage returns HTTP 200 OK");
assertCheck(!str_contains($homeContent, '| CricBail | CricBail'), "Homepage does not output duplicate brand suffix");
assertCheck(str_contains($homeContent, '<link rel="canonical" href="' . $canonicalDomain . '">'), "Homepage canonical link correctly points to {$canonicalDomain}");
assertCheck(str_contains($homeContent, '<meta property="og:site_name" content="CricBail">'), "Homepage includes Open Graph site_name");
assertCheck(str_contains($homeContent, '"@type": "WebSite"') || str_contains($homeContent, '"@type":"WebSite"'), "Homepage contains Schema.org WebSite JSON-LD");
assertCheck(str_contains($homeContent, '"@type": "NewsMediaOrganization"') || str_contains($homeContent, '"@type": "Organization"'), "Homepage contains Organization JSON-LD");
assertCheck(str_contains($homeContent, '<h1'), "Homepage contains dedicated H1 heading hierarchy");

// ---------------------------------------------------------------------
// TEST 3: CATEGORY PAGE SEO & BREADCRUMBS
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 3. Testing Category Page SEO ---" . PHP_EOL;
$cat = Category::where('slug', 'ipl')->first() ?? Category::first();
if ($cat) {
    $catReq = Request::create('/category/' . $cat->slug, 'GET');
    $catRes = $httpKernel->handle($catReq);
    $catContent = $catRes->getContent();

    assertCheck($catRes->getStatusCode() === 200, "Category '/category/{$cat->slug}' returns HTTP 200 OK");
    assertCheck(str_contains($catContent, '<link rel="canonical" href="' . $canonicalDomain . '/category/' . $cat->slug . '">'), "Category has correct canonical URL: {$canonicalDomain}/category/{$cat->slug}");
    assertCheck(!str_contains($catContent, '| CricBail | CricBail'), "Category title does not duplicate brand suffix");
    assertCheck(str_contains($catContent, '"@type": "BreadcrumbList"'), "Category contains BreadcrumbList Schema.org JSON-LD");
    assertCheck(str_contains($catContent, '<h1'), "Category page features an H1 header");
}

// ---------------------------------------------------------------------
// TEST 4: ARTICLE SEO, NEWSARTICLE SCHEMA & SOURCE ATTRIBUTION
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 4. Testing Published Article SEO & NewsArticle JSON-LD ---" . PHP_EOL;
$article = Article::published()->first();
if ($article) {
    $artReq = Request::create('/news/' . $article->slug, 'GET');
    $artRes = $httpKernel->handle($artReq);
    $artContent = $artRes->getContent();

    assertCheck($artRes->getStatusCode() === 200, "Published article '/news/{$article->slug}' returns HTTP 200 OK");
    assertCheck(!str_contains($artContent, '| CricBail | CricBail'), "Article page has clean title without double brand suffix");
    assertCheck(str_contains($artContent, '<link rel="canonical" href="' . $canonicalDomain . '/news/' . $article->slug . '">'), "Article has correct production canonical URL");
    assertCheck(str_contains($artContent, '<meta property="og:type" content="article">'), "Article has og:type=article");
    assertCheck(str_contains($artContent, '<meta name="twitter:card" content="summary_large_image">'), "Article has twitter:card=summary_large_image");
    assertCheck(str_contains($artContent, '"@type": "NewsArticle"'), "Article JSON-LD has '@type': 'NewsArticle'");
    assertCheck(str_contains($artContent, '"headline":'), "Article JSON-LD includes headline property");
    assertCheck(str_contains($artContent, '"datePublished":'), "Article JSON-LD includes truthful datePublished");
    assertCheck(str_contains($artContent, '"author":'), "Article JSON-LD includes author entity");
    assertCheck(str_contains($artContent, '"publisher":'), "Article JSON-LD includes publisher entity");
    assertCheck(str_contains($artContent, '"@type": "BreadcrumbList"'), "Article contains BreadcrumbList schema");
}

// ---------------------------------------------------------------------
// TEST 5: DRAFT AND UNPUBLISHED EXCLUSION
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 5. Testing Draft & Non-Existent Content Exclusion ---" . PHP_EOL;
$fakeReq = Request::create('/news/this-article-does-not-exist-at-all-404', 'GET');
$fakeRes = $httpKernel->handle($fakeReq);
assertCheck($fakeRes->getStatusCode() === 404, "Non-existent article returns HTTP 404 Not Found");

$fakeCatReq = Request::create('/category/bogus-tournament-category-999', 'GET');
$fakeCatRes = $httpKernel->handle($fakeCatReq);
assertCheck($fakeCatRes->getStatusCode() === 404, "Non-existent category returns HTTP 404 Not Found");

// ---------------------------------------------------------------------
// TEST 6: QUIZ SEO & QUIZ SCHEMA
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 6. Testing Quiz Pages SEO & Schema ---" . PHP_EOL;
$quizzesReq = Request::create('/quizzes', 'GET');
$quizzesRes = $httpKernel->handle($quizzesReq);
$quizzesContent = $quizzesRes->getContent();

assertCheck($quizzesRes->getStatusCode() === 200, "Quizzes listing returns HTTP 200 OK");
assertCheck(str_contains($quizzesContent, '<link rel="canonical" href="' . $canonicalDomain . '/quizzes">'), "Quizzes listing canonical points to {$canonicalDomain}/quizzes");

$quiz = Quiz::published()->first();
if ($quiz) {
    $quizReq = Request::create('/quizzes/' . $quiz->slug, 'GET');
    $quizRes = $httpKernel->handle($quizReq);
    $quizContent = $quizRes->getContent();

    assertCheck($quizRes->getStatusCode() === 200, "Quiz detail '/quizzes/{$quiz->slug}' returns HTTP 200 OK");
    assertCheck(str_contains($quizContent, '<link rel="canonical" href="' . $canonicalDomain . '/quizzes/' . $quiz->slug . '">'), "Quiz detail has correct canonical URL");
    assertCheck(str_contains($quizContent, '"@type": "Quiz"'), "Quiz detail contains Schema.org Quiz JSON-LD");
    assertCheck(!str_contains($quizContent, '| CricBail | CricBail'), "Quiz title has single brand suffix");
}

// ---------------------------------------------------------------------
// TEST 7: DYNAMIC XML SITEMAP (/sitemap.xml)
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 7. Testing Dynamic XML Sitemap (/sitemap.xml) ---" . PHP_EOL;
$smReq = Request::create('/sitemap.xml', 'GET');
$smRes = $httpKernel->handle($smReq);
$smContent = $smRes->getContent();

assertCheck($smRes->getStatusCode() === 200, "Sitemap returns HTTP 200 OK");
assertCheck(str_contains($smRes->headers->get('Content-Type'), 'xml'), "Sitemap Content-Type is XML");
assertCheck(str_contains($smContent, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'), "Sitemap contains standard urlset namespace");
assertCheck(str_contains($smContent, '<loc>' . $canonicalDomain . '</loc>'), "Sitemap includes production homepage URL");
assertCheck(str_contains($smContent, '<loc>' . $canonicalDomain . '/news</loc>'), "Sitemap includes /news endpoint");
assertCheck(str_contains($smContent, '<loc>' . $canonicalDomain . '/quizzes</loc>'), "Sitemap includes /quizzes endpoint");
assertCheck(str_contains($smContent, '<lastmod>'), "Sitemap includes lastmod timestamps");
assertCheck(!str_contains($smContent, '/admin'), "Sitemap strictly excludes admin pages");
assertCheck(!str_contains($smContent, '/attempt'), "Sitemap strictly excludes private quiz attempts");
assertCheck(!str_contains($smContent, '8084') && !str_contains($smContent, 'localhost'), "Sitemap never contains local development URLs");

// ---------------------------------------------------------------------
// TEST 8: DYNAMIC GOOGLE NEWS SITEMAP (/news-sitemap.xml)
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 8. Testing Dynamic Google News Sitemap (/news-sitemap.xml) ---" . PHP_EOL;
$nsReq = Request::create('/news-sitemap.xml', 'GET');
$nsRes = $httpKernel->handle($nsReq);
$nsContent = $nsRes->getContent();

assertCheck($nsRes->getStatusCode() === 200, "Google News Sitemap returns HTTP 200 OK");
assertCheck(str_contains($nsRes->headers->get('Content-Type'), 'xml'), "News Sitemap Content-Type is XML");
assertCheck(str_contains($nsContent, 'xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"'), "News Sitemap contains google news namespace");
assertCheck(str_contains($nsContent, '<news:publication>'), "News Sitemap includes publication metadata");
assertCheck(str_contains($nsContent, '<news:name>CricBail</news:name>'), "News publication name is CricBail");
assertCheck(str_contains($nsContent, '<news:language>en</news:language>'), "News publication language is English ('en')");

// ---------------------------------------------------------------------
// TEST 9: ROBOTS.TXT DIRECTIVES
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 9. Testing Robots.txt Crawl Directives ---" . PHP_EOL;
$robReq = Request::create('/robots.txt', 'GET');
$robRes = $httpKernel->handle($robReq);
$robContent = $robRes->getContent();

assertCheck($robRes->getStatusCode() === 200, "robots.txt returns HTTP 200 OK");
assertCheck(str_contains($robContent, 'User-agent: *'), "robots.txt defines default User-agent");
assertCheck(str_contains($robContent, 'Disallow: /admin/'), "robots.txt disallows /admin/");
assertCheck(str_contains($robContent, 'Disallow: /quizzes/attempt/'), "robots.txt disallows private quiz attempts");
assertCheck(str_contains($robContent, 'Sitemap: ' . $canonicalDomain . '/sitemap.xml'), "robots.txt references production sitemap.xml");
assertCheck(str_contains($robContent, 'Sitemap: ' . $canonicalDomain . '/news-sitemap.xml'), "robots.txt references production news-sitemap.xml");
assertCheck(!str_contains($robContent, '8084'), "robots.txt does not leak local development port");

// ---------------------------------------------------------------------
// TEST 10: 301 PERMANENT REDIRECTS FOR LEGACY / DUPLICATE URLS
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 10. Testing 301 Permanent Redirects ---" . PHP_EOL;
$redirCatReq = Request::create('/category/ipl-2026', 'GET');
$redirCatRes = $httpKernel->handle($redirCatReq);
assertCheck($redirCatRes->getStatusCode() === 301, "Legacy category '/category/ipl-2026' returns HTTP 301 permanent redirect");

$redirArtReq = Request::create('/news/ipl-2026-auction-highlights', 'GET');
$redirArtRes = $httpKernel->handle($redirArtReq);
assertCheck($redirArtRes->getStatusCode() === 301, "Legacy test article '/news/ipl-2026-auction-highlights' returns HTTP 301 permanent redirect");

$redirQueryReq = Request::create('/news?category=ipl', 'GET');
$redirQueryRes = $httpKernel->handle($redirQueryReq);
assertCheck($redirQueryRes->getStatusCode() === 301, "Query filter '/news?category=ipl' returns HTTP 301 redirect to canonical /category/ipl");

// ---------------------------------------------------------------------
// TEST 11: ADMIN AUTH NOINDEX & EDITORIAL HEALTH PANEL
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 11. Testing Admin Auth Noindex & Editorial Panel ---" . PHP_EOL;
$loginReq = Request::create('/admin/login', 'GET');
$loginRes = $httpKernel->handle($loginReq);
$loginContent = $loginRes->getContent();

assertCheck($loginRes->getStatusCode() === 200, "Admin login page returns HTTP 200 OK");
assertCheck(str_contains($loginContent, '<meta name="robots" content="noindex, nofollow">'), "Admin login includes 'noindex, nofollow' directive");

$articleModel = new Article();
assertCheck(in_array('source_name', $articleModel->getFillable()) && in_array('source_url', $articleModel->getFillable()), "Article model has 'source_name' and 'source_url' in fillable attributes");

// ---------------------------------------------------------------------
// SUMMARY REPORT
// ---------------------------------------------------------------------
echo PHP_EOL . "=====================================================================" . PHP_EOL;
echo "  TEST SUMMARY: {$totalTests} tests run, " . ($totalTests - $failedTests) . " passed, {$failedTests} failed." . PHP_EOL;
echo "=====================================================================" . PHP_EOL;

if ($failedTests === 0) {
    echo "  >>> ALL SEO AUTOMATED TESTS PASSED SUCCESSFULLY! <<<" . PHP_EOL;
    exit(0);
} else {
    echo "  >>> ERROR: SOME SEO TESTS FAILED! <<<" . PHP_EOL;
    exit(1);
}
