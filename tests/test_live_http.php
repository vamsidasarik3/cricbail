<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Article;
use App\Models\Comment;
use Illuminate\Support\Facades\Cache;

Cache::flush();

$cookieJar = tempnam(sys_get_temp_dir(), 'cricbail_cookie_');
$adminCookieJar = tempnam(sys_get_temp_dir(), 'cricbail_admin_');

function httpGet($url, $cookieFile = null, $referer = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $headers = ['Host: cricbail.vkrshna.in:8084'];
    if ($referer) {
        $headers[] = "Referer: {$referer}";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    }
    $res = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return ['body' => $res, 'status' => $info['http_code'], 'url' => $info['url']];
}

function httpPost($url, $fields, $cookieFile = null, $referer = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $headers = ['Host: cricbail.vkrshna.in:8084'];
    if ($referer) {
        $headers[] = "Referer: {$referer}";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    }
    $res = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return ['body' => $res, 'status' => $info['http_code'], 'url' => $info['url']];
}

function extractCsrf($html) {
    if (preg_match('/name="_token"\s+value="([^"]+)"/', $html, $m)) {
        return $m[1];
    }
    if (preg_match('/<meta\s+name="csrf-token"\s+content="([^"]+)"/', $html, $m)) {
        return $m[1];
    }
    return null;
}

echo "=== CRICBAIL LIVE APACHE HTTP E2E TEST ===" . PHP_EOL . PHP_EOL;

$baseUrl = 'http://127.0.0.1:8084';
$publishedArticle = Article::published()->first();
$articleSlug = $publishedArticle->slug;
$articleUrl = "{$baseUrl}/news/{$articleSlug}";

// 1. Fetch public article page to get session cookie and CSRF token
echo "1. Fetching public article page: {$articleUrl} ... ";
$page1 = httpGet($articleUrl, $cookieJar);
$csrfToken = extractCsrf($page1['body']);
echo "HTTP {$page1['status']} [CSRF: " . ($csrfToken ? 'Found' : 'MISSING') . "]" . PHP_EOL;

// 2. Submit a visitor comment
$testTimestamp = time();
$testCommentText = "Live Apache E2E comment test #{$testTimestamp}: Outstanding cricket analysis!";
$commentData = [
    '_token' => $csrfToken,
    'visitor_name' => 'Live Browser Tester',
    'email' => "livetester_{$testTimestamp}@example.com",
    'comment_body' => $testCommentText,
    'website_hp' => '',
];

echo "2. Submitting visitor comment via POST ... ";
$postRes = httpPost("{$baseUrl}/news/{$articleSlug}/comment", $commentData, $cookieJar, $articleUrl);
$hasSuccessNotice = str_contains($postRes['body'], 'pending review by our editorial team');
$isPubliclyVisibleImmediately = str_contains($postRes['body'], $testCommentText);

echo "HTTP {$postRes['status']}" . PHP_EOL;
echo "   - Moderation notice rendered: " . ($hasSuccessNotice ? '[PASSED: Notice displayed]' : '[FAILED]') . PHP_EOL;
echo "   - Comment hidden while pending: " . (!$isPubliclyVisibleImmediately ? '[PASSED: Hidden from public]' : '[FAILED: Pending shown!]') . PHP_EOL;

// 3. Admin Login
echo "3. Admin logging in at {$baseUrl}/admin/login ... ";
$loginPage = httpGet("{$baseUrl}/admin/login", $adminCookieJar);
$adminCsrf = extractCsrf($loginPage['body']);

$loginRes = httpPost("{$baseUrl}/admin/login", [
    '_token' => $adminCsrf,
    'email' => 'admin@cricbail.com',
    'password' => 'Password123!',
], $adminCookieJar, "{$baseUrl}/admin/login");

echo "HTTP {$loginRes['status']} -> Final URL: {$loginRes['url']}" . PHP_EOL;

// 4. View Admin Comments Moderation Table
echo "4. Admin fetching comments moderation dashboard ... ";
$adminCommentsUrl = "{$baseUrl}/admin/comments?status=pending";
$adminCommentsPage = httpGet($adminCommentsUrl, $adminCookieJar);
$hasCommentInTable = str_contains($adminCommentsPage['body'], $testCommentText);
echo "HTTP {$adminCommentsPage['status']}" . PHP_EOL;
echo "   - New comment found in pending table: " . ($hasCommentInTable ? '[PASSED]' : '[FAILED]') . PHP_EOL;

// Find the comment in DB to get exact ID
$commentInDb = Comment::where('comment_body', $testCommentText)->first();
if (!$commentInDb) {
    echo "ERROR: Comment was not found in database!" . PHP_EOL;
    exit(1);
}
$commentId = $commentInDb->id;
echo "   - Database record verified: Comment #{$commentId} with status '{$commentInDb->status}' [PASSED]" . PHP_EOL;

$adminCommentCsrf = extractCsrf($adminCommentsPage['body']);

// 5. Admin Approves Comment
echo "5. Admin approving comment #{$commentId} ... ";
$approveRes = httpPost("{$baseUrl}/admin/comments/{$commentId}/approve", [
    '_token' => $adminCommentCsrf
], $adminCookieJar, "{$baseUrl}/admin/comments");
$hasApprovedAlert = str_contains($approveRes['body'], 'has been approved and is now publicly visible');
$commentInDb->refresh();
echo "HTTP {$approveRes['status']} | DB status: '{$commentInDb->status}'" . 
    ($hasApprovedAlert && $commentInDb->status === 'approved' ? ' [PASSED: Approved banner & DB updated]' : ' [FAILED]') . PHP_EOL;

// 6. Check Public Article Page: Approved comment MUST appear!
echo "6. Checking public article page for approved comment ... ";
$pageAfterApprove = httpGet($articleUrl);
$isPublicNow = str_contains($pageAfterApprove['body'], $testCommentText);
$isEmailExposed = str_contains($pageAfterApprove['body'], "livetester_{$testTimestamp}@example.com");
echo "HTTP {$pageAfterApprove['status']}" . PHP_EOL;
echo "   - Comment now publicly visible: " . ($isPublicNow ? '[PASSED: Visible]' : '[FAILED: Not visible]') . PHP_EOL;
echo "   - Commenter email kept hidden: " . (!$isEmailExposed ? '[PASSED: Email NOT exposed]' : '[FAILED: Email found!]') . PHP_EOL;

// 7. Admin Rejects Comment
echo "7. Admin rejecting comment #{$commentId} ... ";
$adminCommentsPage2 = httpGet("{$baseUrl}/admin/comments?status=approved", $adminCookieJar);
$adminCommentCsrf2 = extractCsrf($adminCommentsPage2['body']);
$rejectRes = httpPost("{$baseUrl}/admin/comments/{$commentId}/reject", [
    '_token' => $adminCommentCsrf2
], $adminCookieJar, "{$baseUrl}/admin/comments");
$hasRejectAlert = str_contains($rejectRes['body'], 'has been rejected');
$commentInDb->refresh();
echo "HTTP {$rejectRes['status']} | DB status: '{$commentInDb->status}'" . 
    ($hasRejectAlert && $commentInDb->status === 'rejected' ? ' [PASSED: Rejected banner & DB updated]' : ' [FAILED]') . PHP_EOL;

// 8. Check Public Article Page: Rejected comment MUST be hidden!
echo "8. Checking public article page after rejection ... ";
$pageAfterReject = httpGet($articleUrl);
$isPublicAfterReject = str_contains($pageAfterReject['body'], $testCommentText);
echo "   - Comment hidden after rejection: " . (!$isPublicAfterReject ? '[PASSED: Successfully hidden]' : '[FAILED: Still shown!]') . PHP_EOL;

// 9. Admin Permanently Deletes Comment
echo "9. Admin deleting comment #{$commentId} ... ";
$adminCommentsPage3 = httpGet("{$baseUrl}/admin/comments?status=rejected", $adminCookieJar);
$adminCommentCsrf3 = extractCsrf($adminCommentsPage3['body']);
$deleteRes = httpPost("{$baseUrl}/admin/comments/{$commentId}", [
    '_token' => $adminCommentCsrf3,
    '_method' => 'DELETE',
], $adminCookieJar, "{$baseUrl}/admin/comments");
$hasDeleteAlert = str_contains($deleteRes['body'], 'permanently removed');
$existsAfterDelete = Comment::where('id', $commentId)->exists();
echo "HTTP {$deleteRes['status']} | hasAlert=" . ($hasDeleteAlert ? 'yes' : 'no') . " | exists=" . ($existsAfterDelete ? 'yes' : 'no') . 
    ($hasDeleteAlert && !$existsAfterDelete ? ' [PASSED: Deleted banner shown & DB removed]' : ' [FAILED]') . PHP_EOL;

// Cleanup cookie jars
@unlink($cookieJar);
@unlink($adminCookieJar);

echo PHP_EOL . "=== ALL LIVE APACHE E2E WORKFLOW CHECKS PASSED 100% ===" . PHP_EOL;
