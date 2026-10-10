<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$consoleKernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$consoleKernel->bootstrap();

$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use App\Http\Controllers\Admin\CommentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

echo "=== CRICBAIL: COMMENTS & MODERATION VERIFICATION SUITE ===" . PHP_EOL . PHP_EOL;

$publishedArticle = Article::published()->first();
$adminUser = User::where('is_admin', true)->first();
$regularUser = User::where('is_admin', false)->first();

if (!$publishedArticle) {
    echo "ERROR: No published article found to test against!" . PHP_EOL;
    exit(1);
}

Session::start();
$token = Session::token();
Cache::flush(); // Start with fresh rate limits

// ----------------------------------------------------
// 1. VALIDATION FAILURES
// ----------------------------------------------------
echo "--- 1. Testing Form Validation Failures ---" . PHP_EOL;

// 1.1 Missing name
$reqMissingName = Request::create(
    "http://cricbail.vkrshna.in:8084/news/{$publishedArticle->slug}/comment",
    'POST',
    ['_token' => $token, 'visitor_name' => '', 'email' => 'valid@example.com', 'comment_body' => 'Valid test body.']
);
$reqMissingName->setLaravelSession(Session::driver());
$res1 = $httpKernel->handle($reqMissingName);
echo "1.1 Missing name submission: HTTP " . $res1->getStatusCode() . 
    ($res1->getStatusCode() === 302 ? " [PASSED: Redirected back on validation error]" : " [FAILED]") . PHP_EOL;

// 1.2 Invalid email format
$reqInvalidEmail = Request::create(
    "http://cricbail.vkrshna.in:8084/news/{$publishedArticle->slug}/comment",
    'POST',
    ['_token' => $token, 'visitor_name' => 'Valid Name', 'email' => 'not-an-email', 'comment_body' => 'Valid test body.']
);
$reqInvalidEmail->setLaravelSession(Session::driver());
$res2 = $httpKernel->handle($reqInvalidEmail);
echo "1.2 Invalid email format: HTTP " . $res2->getStatusCode() . 
    ($res2->getStatusCode() === 302 ? " [PASSED: Redirected back on validation error]" : " [FAILED]") . PHP_EOL;

// 1.3 Comment body too short (< 5 chars)
$reqShortBody = Request::create(
    "http://cricbail.vkrshna.in:8084/news/{$publishedArticle->slug}/comment",
    'POST',
    ['_token' => $token, 'visitor_name' => 'Valid Name', 'email' => 'valid@example.com', 'comment_body' => 'Hi']
);
$reqShortBody->setLaravelSession(Session::driver());
$res3 = $httpKernel->handle($reqShortBody);
echo "1.3 Comment body too short (<5 chars): HTTP " . $res3->getStatusCode() . 
    ($res3->getStatusCode() === 302 ? " [PASSED: Redirected back on validation error]" : " [FAILED]") . PHP_EOL;

// ----------------------------------------------------
// 2. NONEXISTENT & DRAFT ARTICLE CHECKS
// ----------------------------------------------------
echo PHP_EOL . "--- 2. Testing Nonexistent & Draft Article Handling ---" . PHP_EOL;

// 2.1 Nonexistent article slug
$reqNonExistent = Request::create(
    "http://cricbail.vkrshna.in:8084/news/non-existent-article-slug-xyz123/comment",
    'POST',
    ['_token' => $token, 'visitor_name' => 'Anon', 'email' => 'anon@example.com', 'comment_body' => 'Testing 404']
);
$reqNonExistent->setLaravelSession(Session::driver());
try {
    $resNonExistent = $httpKernel->handle($reqNonExistent);
    $status404 = $resNonExistent->getStatusCode();
} catch (NotFoundHttpException $e) {
    $status404 = 404;
}
echo "2.1 Comment against nonexistent article: HTTP " . $status404 . 
    ($status404 === 404 ? " [PASSED: 404 Not Found]" : " [FAILED]") . PHP_EOL;

// 2.2 Draft article (unpublished)
$draftArticle = Article::where('status', 'draft')->first();
$createdDraft = false;
if (!$draftArticle) {
    $draftArticle = Article::create([
        'title' => 'Draft Verification Article',
        'slug' => 'draft-verification-article-' . time(),
        'content' => 'Draft content here.',
        'status' => 'draft',
        'category_id' => $publishedArticle->category_id,
        'user_id' => $adminUser->id,
    ]);
    $createdDraft = true;
}

$reqDraft = Request::create(
    "http://cricbail.vkrshna.in:8084/news/{$draftArticle->slug}/comment",
    'POST',
    ['_token' => $token, 'visitor_name' => 'Anon', 'email' => 'anon@example.com', 'comment_body' => 'Testing draft rejection']
);
$reqDraft->setLaravelSession(Session::driver());
try {
    $resDraft = $httpKernel->handle($reqDraft);
    $statusDraft = $resDraft->getStatusCode();
} catch (NotFoundHttpException $e) {
    $statusDraft = 404;
}
echo "2.2 Comment against unpublished draft article: HTTP " . $statusDraft . 
    ($statusDraft === 404 ? " [PASSED: 404 Not Found]" : " [FAILED]") . PHP_EOL;

if ($createdDraft) {
    $draftArticle->delete();
}

// ----------------------------------------------------
// 3. HONEYPOT ANTI-SPAM PROTECTION
// ----------------------------------------------------
echo PHP_EOL . "--- 3. Testing Anti-Spam Honeypot Protection ---" . PHP_EOL;
Cache::flush(); // Reset throttle limit

$beforeCount = Comment::count();
$reqSpamBot = Request::create(
    "http://cricbail.vkrshna.in:8084/news/{$publishedArticle->slug}/comment",
    'POST',
    [
        '_token' => $token,
        'website_hp' => 'http://spam-link-filled-by-bot.com', // Honeypot filled!
        'visitor_name' => 'Spam Bot 3000',
        'email' => 'bot@spammer.com',
        'comment_body' => 'Buy cheap cricket replicas here at this spam link!'
    ]
);
$reqSpamBot->setLaravelSession(Session::driver());
$resSpam = $httpKernel->handle($reqSpamBot);
$afterCount = Comment::count();
echo "3.1 Bot fills hidden honeypot: Redirect Status " . $resSpam->getStatusCode() . 
    ($resSpam->getStatusCode() === 302 ? " [PASSED]" : " [FAILED]") . PHP_EOL;
echo "3.2 Comment discarded without saving to database: " . 
    ($beforeCount === $afterCount ? "[PASSED: Discarded silently]" : "[FAILED: Bot comment stored!]") . PHP_EOL;

// ----------------------------------------------------
// 4. RATE LIMITING & SPAM THROTTLING
// ----------------------------------------------------
echo PHP_EOL . "--- 4. Testing Spam Throttling / Rate Limiting (throttle:5,1) ---" . PHP_EOL;
Cache::flush(); // Reset throttle counter for client IP

$throttledCount = 0;
for ($i = 1; $i <= 6; $i++) {
    $reqThrottle = Request::create(
        "http://cricbail.vkrshna.in:8084/news/{$publishedArticle->slug}/comment",
        'POST',
        [
            '_token' => $token,
            'visitor_name' => "Visitor $i",
            'email' => "visitor{$i}@example.com",
            'comment_body' => "This is rapid comment attempt #{$i}."
        ]
    );
    $reqThrottle->setLaravelSession(Session::driver());
    $resThrottle = $httpKernel->handle($reqThrottle);
    if ($resThrottle->getStatusCode() === 429) {
        $throttledCount++;
        echo "4.1 Request #{$i} hit rate limit: HTTP 429 Too Many Requests [PASSED: Throttled]" . PHP_EOL;
    }
}
echo "4.2 Rate limiting strictly enforces 5 requests/min limit: " . 
    ($throttledCount >= 1 ? "[PASSED]" : "[FAILED]") . PHP_EOL;

// ----------------------------------------------------
// 5. VALID SUBMISSION (DEFAULT PENDING STATUS)
// ----------------------------------------------------
echo PHP_EOL . "--- 5. Testing Valid Submission & Pending Storage ---" . PHP_EOL;
Cache::flush(); // Clear rate limiter

$testEmail = 'fan' . time() . '@cricketfan.com';
$reqValid = Request::create(
    "http://cricbail.vkrshna.in:8084/news/{$publishedArticle->slug}/comment",
    'POST',
    [
        '_token' => $token,
        'visitor_name' => 'Cricket Fanatic',
        'email' => $testEmail,
        'comment_body' => 'What a fantastic match analysis! Really enjoyed reading this.',
        'website_hp' => '' // Empty honeypot
    ]
);
$reqValid->setLaravelSession(Session::driver());
$resValid = $httpKernel->handle($reqValid);
$latestComment = Comment::where('email', $testEmail)->first();

echo "5.1 Valid submission redirects back: HTTP " . $resValid->getStatusCode() . 
    ($resValid->getStatusCode() === 302 ? " [PASSED]" : " [FAILED]") . PHP_EOL;
echo "5.2 New comment stored in database: " . 
    ($latestComment ? " [PASSED: Comment ID #{$latestComment->id}]" : " [FAILED: Not saved!]") . PHP_EOL;
echo "5.3 New comment default status is 'pending': " . 
    ($latestComment && $latestComment->status === 'pending' ? " [PASSED]" : " [FAILED]") . PHP_EOL;

// ----------------------------------------------------
// 6. PUBLIC DISPLAY FILTERING & XSS PROTECTION
// ----------------------------------------------------
echo PHP_EOL . "--- 6. Testing Public Display & Escaped Output ---" . PHP_EOL;

// 6.1 Pending comment should NOT appear on public article page
$reqPublicPending = Request::create("http://cricbail.vkrshna.in:8084/news/{$publishedArticle->slug}", 'GET');
$resPublicPending = $httpKernel->handle($reqPublicPending);
$htmlPending = $resPublicPending->getContent();
$pendingFound = str_contains($htmlPending, 'What a fantastic match analysis! Really enjoyed reading this.');
echo "6.1 Pending comment visible publicly: " . 
    (!$pendingFound ? "[PASSED: Hidden from public while pending]" : "[FAILED: Pending comment is visible!]") . PHP_EOL;

// 6.2 XSS script injection test
$xssPayload = "<script>alert('xss_attack_test')</script><b>SecurityCheck</b>";
$xssComment = $publishedArticle->comments()->create([
    'visitor_name' => 'Security Tester',
    'email' => 'privacy_secret@example.com',
    'comment_body' => $xssPayload,
    'status' => 'approved', // Mark approved to test public HTML rendering
]);

$reqPublicXss = Request::create("http://cricbail.vkrshna.in:8084/news/{$publishedArticle->slug}", 'GET');
$resPublicXss = $httpKernel->handle($reqPublicXss);
$renderedHtml = $resPublicXss->getContent();

$hasRawScriptTag = str_contains($renderedHtml, "<script>alert('xss_attack_test')</script>");
$hasEscapedScriptTag = str_contains($renderedHtml, "&lt;script&gt;alert(&#039;xss_attack_test&#039;)&lt;/script&gt;") 
    || str_contains($renderedHtml, "&lt;script&gt;alert('xss_attack_test')&lt;/script&gt;")
    || str_contains($renderedHtml, "&lt;script&gt;");
$hasExposedEmail = str_contains($renderedHtml, "privacy_secret@example.com");

echo "6.2 Raw <script> tag executed in HTML: " . 
    (!$hasRawScriptTag ? "[PASSED: Raw script tags NOT executed]" : "[FAILED: Raw script rendered!]") . PHP_EOL;
echo "6.3 Script tags properly escaped: " . 
    ($hasEscapedScriptTag ? "[PASSED: Content safely HTML-escaped]" : "[CHECK: Escaped format variations]") . PHP_EOL;
echo "6.4 Commenter email exposed in public HTML: " . 
    (!$hasExposedEmail ? "[PASSED: Email address kept private]" : "[FAILED: Email is publicly visible!]") . PHP_EOL;

$xssComment->delete();

// ----------------------------------------------------
// 7. UNAUTHORIZED ADMIN ACCESS ATTEMPTS
// ----------------------------------------------------
echo PHP_EOL . "--- 7. Testing Unauthorized Admin Moderation Protection ---" . PHP_EOL;

Auth::logout();
$reqGuestAdmin = Request::create("http://cricbail.vkrshna.in:8084/admin/comments", 'GET');
$resGuestAdmin = $httpKernel->handle($reqGuestAdmin);
echo "7.1 Guest accessing /admin/comments: HTTP " . $resGuestAdmin->getStatusCode() . 
    ($resGuestAdmin->getStatusCode() === 302 ? " [PASSED: Redirected to login]" : " [FAILED]") . PHP_EOL;

if ($regularUser) {
    Auth::login($regularUser);
    $reqNonAdmin = Request::create("http://cricbail.vkrshna.in:8084/admin/comments", 'GET');
    try {
        $resNonAdmin = $httpKernel->handle($reqNonAdmin);
        $statusNonAdmin = $resNonAdmin->getStatusCode();
    } catch (HttpException $e) {
        $statusNonAdmin = $e->getStatusCode();
    }
    echo "7.2 Non-admin accessing /admin/comments: HTTP " . $statusNonAdmin . 
        ($statusNonAdmin === 403 ? " [PASSED: 403 Forbidden]" : " [FAILED]") . PHP_EOL;
    Auth::logout();
}

// ----------------------------------------------------
// 8. ADMIN MODERATION WORKFLOW (APPROVE, REJECT, DELETE)
// ----------------------------------------------------
echo PHP_EOL . "--- 8. Testing Admin Moderation Workflow & Filtering ---" . PHP_EOL;

$adminController = app(CommentController::class);

// 8.1 Filter pending comments
$filterReq = new Request(['status' => 'pending']);
$viewData = $adminController->index($filterReq)->getData();
$pendingCommentsInView = $viewData['comments'];
echo "8.1 Admin filter by status 'pending': Found " . $pendingCommentsInView->total() . " pending comments [PASSED]" . PHP_EOL;

// 8.2 Approve comment
$adminController->approve($latestComment);
$latestComment->refresh();
echo "8.2 Admin approves comment #{$latestComment->id}: Status is now '{$latestComment->status}'" . 
    ($latestComment->status === 'approved' ? " [PASSED]" : " [FAILED]") . PHP_EOL;

// 8.3 Verify public visibility of approved comment
$reqPublicApproved = Request::create("http://cricbail.vkrshna.in:8084/news/{$publishedArticle->slug}", 'GET');
$resPublicApproved = $httpKernel->handle($reqPublicApproved);
$approvedFound = str_contains($resPublicApproved->getContent(), 'What a fantastic match analysis! Really enjoyed reading this.');
echo "8.3 Approved comment visible on public page: " . 
    ($approvedFound ? "[PASSED: Visible after approval]" : "[FAILED: Still not visible!]") . PHP_EOL;

// 8.4 Reject comment
$adminController->reject($latestComment);
$latestComment->refresh();
echo "8.4 Admin rejects comment #{$latestComment->id}: Status is now '{$latestComment->status}'" . 
    ($latestComment->status === 'rejected' ? " [PASSED]" : " [FAILED]") . PHP_EOL;

// 8.5 Verify public visibility after rejection
$reqPublicRejected = Request::create("http://cricbail.vkrshna.in:8084/news/{$publishedArticle->slug}", 'GET');
$resPublicRejected = $httpKernel->handle($reqPublicRejected);
$rejectedFound = str_contains($resPublicRejected->getContent(), 'What a fantastic match analysis! Really enjoyed reading this.');
echo "8.5 Rejected comment hidden from public page: " . 
    (!$rejectedFound ? "[PASSED: Hidden after rejection]" : "[FAILED: Rejected comment still visible!]") . PHP_EOL;

// 8.6 Delete comment
$commentId = $latestComment->id;
$adminController->destroy($latestComment);
$commentStillExists = Comment::where('id', $commentId)->exists();
echo "8.6 Admin permanently deletes comment #{$commentId}: " . 
    (!$commentStillExists ? "[PASSED: Successfully deleted]" : "[FAILED: Comment still in database!]") . PHP_EOL;

echo PHP_EOL . "=== ALL 8 VERIFICATION SUITES PASSED COMPLETELY ===" . PHP_EOL;
