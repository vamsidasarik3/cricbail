<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Article;
use App\Models\Category;

$baseUrl = 'http://127.0.0.1:8084';
$hostHeader = 'Host: cricbail.vkrshna.in:8084';
$cookieFile = tempnam(sys_get_temp_dir(), 'admin_live_test_');

function requestHttp($url, $method = 'GET', $data = [], $cookieFile = null, $referer = null) {
    global $hostHeader;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $headers = [$hostHeader];
    if ($referer) {
        $headers[] = "Referer: {$referer}";
    }
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    }
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $body = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return ['body' => $body, 'status' => $info['http_code'], 'url' => $info['url']];
}

function parseCsrf($html) {
    if (preg_match('/name="_token"\s+value="([^"]+)"/', $html, $m)) {
        return $m[1];
    }
    if (preg_match('/<meta\s+name="csrf-token"\s+content="([^"]+)"/', $html, $m)) {
        return $m[1];
    }
    return null;
}

echo "==========================================================" . PHP_EOL;
echo "     LIVE HTTP APACHE ADMIN MANAGEMENT TEST SUITE         " . PHP_EOL;
echo "==========================================================" . PHP_EOL . PHP_EOL;

// 1. GUEST ACCESS REDIRECTION
echo "--- 1. Testing Guest Access Redirection ---" . PHP_EOL;
$guestResp = requestHttp("{$baseUrl}/admin/dashboard");
if (str_contains($guestResp['url'], '/admin/login')) {
    echo "  [PASS] Unauthenticated visit redirected to /admin/login (Final URL: {$guestResp['url']})" . PHP_EOL;
} else {
    echo "  [FAIL] Guest was not redirected to /admin/login. URL: {$guestResp['url']}" . PHP_EOL;
    exit(1);
}

// 2. ADMIN LOGIN
echo PHP_EOL . "--- 2. Testing Administrator Login ---" . PHP_EOL;
$loginPage = requestHttp("{$baseUrl}/admin/login", 'GET', [], $cookieFile);
$csrfToken = parseCsrf($loginPage['body']);

if (!$csrfToken) {
    echo "  [FAIL] Could not extract CSRF token from login page." . PHP_EOL;
    exit(1);
}

$loginResp = requestHttp("{$baseUrl}/admin/login", 'POST', [
    '_token' => $csrfToken,
    'email' => 'admin@cricbail.com',
    'password' => 'Password123!',
], $cookieFile, "{$baseUrl}/admin/login");

if (str_contains($loginResp['url'], '/admin') && !str_contains($loginResp['url'], '/admin/login')) {
    echo "  [PASS] Logged in successfully as admin! Final URL: {$loginResp['url']}" . PHP_EOL;
} else {
    echo "  [FAIL] Admin login failed. Final URL: {$loginResp['url']}" . PHP_EOL;
    exit(1);
}

// 3. ADMIN DASHBOARD
echo PHP_EOL . "--- 3. Testing Admin Dashboard Metrics & Links ---" . PHP_EOL;
$dashResp = requestHttp("{$baseUrl}/admin/dashboard", 'GET', [], $cookieFile);
if ($dashResp['status'] === 200 && str_contains($dashResp['body'], 'Administration Overview')) {
    echo "  [PASS] Admin Dashboard loaded with HTTP 200 OK." . PHP_EOL;

    // Check metric labels
    $requiredMetrics = [
        'PUBLISHED NEWS',
        'DRAFT ARTICLES',
        'CATEGORIES',
        'PENDING COMMENTS',
        'ACTIVE QUIZZES',
        'TOTAL ATTEMPTS',
    ];
    foreach ($requiredMetrics as $metric) {
        if (str_contains($dashResp['body'], $metric)) {
            echo "  [PASS] Dashboard contains metric card: '{$metric}'" . PHP_EOL;
        } else {
            echo "  [FAIL] Missing metric: '{$metric}'" . PHP_EOL;
            exit(1);
        }
    }
} else {
    echo "  [FAIL] Admin dashboard failed to load properly. Status: {$dashResp['status']}" . PHP_EOL;
    exit(1);
}

// 4. ARTICLE MANAGEMENT LIVE OPERATIONS
echo PHP_EOL . "--- 4. Testing Article Management HTTP Endpoints ---" . PHP_EOL;
// A. Articles Index
$articlesResp = requestHttp("{$baseUrl}/admin/articles", 'GET', [], $cookieFile);
if ($articlesResp['status'] === 200 && str_contains($articlesResp['body'], 'Cricket News & Articles')) {
    echo "  [PASS] Articles Index loaded with HTTP 200 OK." . PHP_EOL;
} else {
    echo "  [FAIL] Articles Index failed to load." . PHP_EOL;
    exit(1);
}

// B. Articles Create Page
$createPageResp = requestHttp("{$baseUrl}/admin/articles/create", 'GET', [], $cookieFile);
if ($createPageResp['status'] === 200 && str_contains($createPageResp['body'], 'Write New Cricket Article')) {
    echo "  [PASS] Article Create Page loaded with HTTP 200 OK." . PHP_EOL;
} else {
    echo "  [FAIL] Article Create Page failed to load." . PHP_EOL;
    exit(1);
}

// C. Create New Article via HTTP POST
$articleCsrf = parseCsrf($createPageResp['body']);
$uniqueId = time();
$category = Category::first();

$createArticleResp = requestHttp("{$baseUrl}/admin/articles", 'POST', [
    '_token' => $articleCsrf,
    'title' => "Live HTTP Article {$uniqueId}",
    'category_id' => $category->id,
    'excerpt' => 'Live test excerpt standfirst.',
    'content' => 'Live test article body content created over HTTP.',
    'status' => 'draft',
], $cookieFile, "{$baseUrl}/admin/articles/create");

$createdArticle = Article::where('title', "Live HTTP Article {$uniqueId}")->first();
if ($createdArticle && $createdArticle->status === 'draft') {
    echo "  [PASS] Article created over HTTP POST (ID: {$createdArticle->id}, Status: {$createdArticle->status})" . PHP_EOL;
} else {
    echo "  [FAIL] Article creation over HTTP POST failed." . PHP_EOL;
    exit(1);
}

// D. Publish Article via HTTP POST
$articlesPage = requestHttp("{$baseUrl}/admin/articles", 'GET', [], $cookieFile);
$csrf = parseCsrf($articlesPage['body']);

$publishResp = requestHttp("{$baseUrl}/admin/articles/{$createdArticle->id}/publish", 'POST', [
    '_token' => $csrf,
], $cookieFile, "{$baseUrl}/admin/articles");

$createdArticle->refresh();
if ($createdArticle->status === 'published') {
    echo "  [PASS] Article published over HTTP POST (Status: {$createdArticle->status})" . PHP_EOL;
} else {
    echo "  [FAIL] Article publish over HTTP POST failed." . PHP_EOL;
    exit(1);
}

// E. Unpublish Article via HTTP POST
$unpublishResp = requestHttp("{$baseUrl}/admin/articles/{$createdArticle->id}/unpublish", 'POST', [
    '_token' => $csrf,
], $cookieFile, "{$baseUrl}/admin/articles");

$createdArticle->refresh();
if ($createdArticle->status === 'draft') {
    echo "  [PASS] Article reverted to draft over HTTP POST (Status: {$createdArticle->status})" . PHP_EOL;
} else {
    echo "  [FAIL] Article unpublish over HTTP POST failed." . PHP_EOL;
    exit(1);
}

// F. Delete Article via HTTP DELETE
$deleteResp = requestHttp("{$baseUrl}/admin/articles/{$createdArticle->id}", 'POST', [
    '_token' => $csrf,
    '_method' => 'DELETE',
], $cookieFile, "{$baseUrl}/admin/articles");

if (Article::find($createdArticle->id) === null) {
    echo "  [PASS] Article deleted over HTTP DELETE successfully." . PHP_EOL;
} else {
    echo "  [FAIL] Article deletion over HTTP failed." . PHP_EOL;
    exit(1);
}

// 5. CATEGORY MANAGEMENT LIVE OPERATIONS
echo PHP_EOL . "--- 5. Testing Category Management HTTP Endpoints ---" . PHP_EOL;
// A. Categories Index
$catIndexResp = requestHttp("{$baseUrl}/admin/categories", 'GET', [], $cookieFile);
if ($catIndexResp['status'] === 200 && str_contains($catIndexResp['body'], 'Article Categories')) {
    echo "  [PASS] Categories Index loaded with HTTP 200 OK." . PHP_EOL;
} else {
    echo "  [FAIL] Categories Index failed to load." . PHP_EOL;
    exit(1);
}

// B. Create Category via HTTP POST
$catCsrf = parseCsrf($catIndexResp['body']);
$catName = "Live Category {$uniqueId}";
$createCatResp = requestHttp("{$baseUrl}/admin/categories", 'POST', [
    '_token' => $catCsrf,
    'name' => $catName,
    'description' => 'A category created in live HTTP test.',
], $cookieFile, "{$baseUrl}/admin/categories");

$newCat = Category::where('name', $catName)->first();
if ($newCat) {
    echo "  [PASS] Category created over HTTP POST (ID: {$newCat->id}, Slug: {$newCat->slug})" . PHP_EOL;
} else {
    echo "  [FAIL] Category creation failed." . PHP_EOL;
    exit(1);
}

// C. Test Deletion Protection on category with articles
$existingCategoryWithArticles = Category::has('articles')->first();
if ($existingCategoryWithArticles) {
    $deleteProtectedResp = requestHttp("{$baseUrl}/admin/categories/{$existingCategoryWithArticles->id}", 'POST', [
        '_token' => $catCsrf,
        '_method' => 'DELETE',
    ], $cookieFile, "{$baseUrl}/admin/categories");

    $categoryStillPresent = Category::find($existingCategoryWithArticles->id);
    if ($categoryStillPresent && str_contains($deleteProtectedResp['body'], 'Cannot delete category')) {
        echo "  [PASS] Relational protection confirmed over HTTP: Cannot delete category with articles (flash error displayed)." . PHP_EOL;
    } else {
        echo "  [FAIL] Category with articles was not protected or error not rendered." . PHP_EOL;
        exit(1);
    }
}

// D. Delete the newly created empty category
$deleteEmptyResp = requestHttp("{$baseUrl}/admin/categories/{$newCat->id}", 'POST', [
    '_token' => $catCsrf,
    '_method' => 'DELETE',
], $cookieFile, "{$baseUrl}/admin/categories");

if (Category::find($newCat->id) === null) {
    echo "  [PASS] Empty category deleted over HTTP DELETE successfully." . PHP_EOL;
} else {
    echo "  [FAIL] Empty category deletion over HTTP failed." . PHP_EOL;
    exit(1);
}

echo PHP_EOL . "==========================================================" . PHP_EOL;
echo "  ALL LIVE HTTP APACHE ADMIN OPERATIONS PASSED!           " . PHP_EOL;
echo "==========================================================" . PHP_EOL;
