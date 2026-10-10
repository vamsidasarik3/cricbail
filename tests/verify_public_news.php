<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Http\Request;

function testRoute($kernel, $method, $uri, $expectedStatus, $contentKeywords = [], $forbiddenKeywords = []) {
    $request = Request::create('http://cricbail.vkrshna.in:8084' . $uri, $method);
    try {
        $response = $kernel->handle($request);
        $status = $response->getStatusCode();
        $content = $response->getContent();
        $kernel->terminate($request, $response);
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        $status = $e->getStatusCode();
        $content = '';
    }

    $pass = ($status === $expectedStatus);
    $keywordPass = true;
    foreach ($contentKeywords as $kw) {
        if (!str_contains($content, $kw)) {
            $keywordPass = false;
            echo "   [FAIL KEYWORD]: Missing expected '{$kw}' in {$uri}" . PHP_EOL;
        }
    }
    foreach ($forbiddenKeywords as $fkw) {
        if (str_contains($content, $fkw)) {
            $keywordPass = false;
            echo "   [FAIL FORBIDDEN]: Found forbidden '{$fkw}' in {$uri}" . PHP_EOL;
        }
    }

    $result = ($pass && $keywordPass) ? "PASSED" : "FAILED (Status: {$status}, Expected: {$expectedStatus})";
    echo "{$method} {$uri} -> Status {$status}: [{$result}]" . PHP_EOL;
    return $pass && $keywordPass;
}

echo "=== CRICBAIL PUBLIC WEBSITE VERIFICATION SUITE ===" . PHP_EOL;

// 1. Homepage
testRoute($kernel, 'GET', '/', 200, [
    'CRIC', 'BAIL', 'Top Story', 'Latest Cricket News', 'Test Your Cricket Knowledge', 'IPL'
]);

// 2. News Listing
testRoute($kernel, 'GET', '/news', 200, [
    'All Cricket News', 'Showing', 'Filter', 'Tactical Evolution'
]);

// 3. News Listing with Category Filter
testRoute($kernel, 'GET', '/news?category=ipl', 200, [
    'IPL News', 'IPL 2026 Mega Retentions'
]);

// 4. Category Page
testRoute($kernel, 'GET', '/category/ipl', 200, [
    'IPL 2026', 'Category Desk'
]);

// 5. Missing Category -> 404
testRoute($kernel, 'GET', '/category/non-existent-cricket-category', 404);

// 6. Article Detail Page
testRoute($kernel, 'GET', '/news/tactical-evolution-middle-overs-spin-chokes-modern-t20', 200, [
    'Tactical Evolution', 'CricBail Senior Cricket Correspondent', 'Middle-Overs Spin',
    'Reader Discussion', 'Leave a Thoughtful Comment', 'Related Stories', 'canonical'
]);

// 7. Unpublished / Draft Article -> MUST BE 404
testRoute($kernel, 'GET', '/news/draft-tactical-analysis-upcoming-icc-champions-trophy', 404);

// 8. Missing Article Slug -> 404
testRoute($kernel, 'GET', '/news/completely-bogus-article-slug', 404);

// 9. About Page
testRoute($kernel, 'GET', '/about', 200, [
    'About CricBail', 'Editorial Mission'
]);

// 10. Contact Page
testRoute($kernel, 'GET', '/contact', 200, [
    'Contact CricBail', 'Send Message to Editorial Team'
]);

// 11. Privacy Policy Page
testRoute($kernel, 'GET', '/privacy-policy', 200, [
    'Privacy Policy', 'Reader Comments', 'Cookies & Session'
]);

// 12. XML Sitemap
testRoute($kernel, 'GET', '/sitemap.xml', 200, [
    '<?xml', '<urlset', 'http://cricbail.vkrshna.in:8084/news', 'tactical-evolution'
], [
    'draft-tactical-analysis' // Ensure draft is NOT in sitemap
]);

echo "=== ALL AUTOMATED ROUTE TESTS COMPLETED ===" . PHP_EOL;
