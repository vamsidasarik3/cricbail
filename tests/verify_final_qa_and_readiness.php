<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$consoleKernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$consoleKernel->bootstrap();

$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

echo "=====================================================================" . PHP_EOL;
echo "  CRICBAIL: FINAL QUALITY ASSURANCE & PRODUCTION READINESS AUDIT     " . PHP_EOL;
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

// ---------------------------------------------------------------------
// TEST 1: HOMEPAGE & ARTICLE BROWSING
// ---------------------------------------------------------------------
echo "--- 1. Testing Homepage & Article Browsing ---" . PHP_EOL;
$homeReq = Request::create('/', 'GET');
$homeRes = $httpKernel->handle($homeReq);
assertCheck($homeRes->getStatusCode() === 200, "Homepage returns HTTP 200 OK");
assertCheck(str_contains($homeRes->getContent(), 'CricBail') || str_contains($homeRes->getContent(), 'CRICBAIL'), "Homepage contains CricBail branding");
assertCheck(str_contains($homeRes->getContent(), 'IPL') && str_contains($homeRes->getContent(), 'Quizzes'), "Homepage contains primary cricket navigation");

$newsReq = Request::create('/news', 'GET');
$newsRes = $httpKernel->handle($newsReq);
assertCheck($newsRes->getStatusCode() === 200, "News listing returns HTTP 200 OK");

$firstArticle = Article::published()->first();
if ($firstArticle) {
    $articleReq = Request::create("/news/{$firstArticle->slug}", 'GET');
    $articleRes = $httpKernel->handle($articleReq);
    assertCheck($articleRes->getStatusCode() === 200, "Published article details page returns HTTP 200 OK");
    assertCheck(str_contains($articleRes->getContent(), $firstArticle->title), "Article page renders headline");
    assertCheck(str_contains($articleRes->getContent(), 'Reader Discussion'), "Article page renders discussion section");
} else {
    assertCheck(false, "No published article found to test details page");
}

// ---------------------------------------------------------------------
// TEST 2: CATEGORY NAVIGATION & PAGINATION
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 2. Testing Category Navigation & Pagination ---" . PHP_EOL;
$firstCategory = Category::first();
if ($firstCategory) {
    $catReq = Request::create("/category/{$firstCategory->slug}", 'GET');
    $catRes = $httpKernel->handle($catReq);
    assertCheck($catRes->getStatusCode() === 200, "Category page '/category/{$firstCategory->slug}' returns HTTP 200 OK");
    assertCheck(str_contains($catRes->getContent(), $firstCategory->name), "Category page displays category name");
}

$missingCatReq = Request::create("/category/nonexistent-category-slug-999", 'GET');
$missingCatRes = $httpKernel->handle($missingCatReq);
assertCheck($missingCatRes->getStatusCode() === 404, "Nonexistent category returns HTTP 404");
assertCheck(str_contains($missingCatRes->getContent(), 'Howzat?!') || str_contains($missingCatRes->getContent(), '404'), "Custom 404 cricket error page displayed");

// ---------------------------------------------------------------------
// TEST 3: COMMENT SUBMISSION, MODERATION & XSS ESCAPING
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 3. Testing Comment Moderation & Security ---" . PHP_EOL;
Session::start();
$token = Session::token();
$xssPayload = "<script>alert('xss-test')</script> Great bowling!";
$testName = "QA Visitor";

$commentReq = Request::create("/news/{$firstArticle->slug}/comment", 'POST', [
    '_token' => $token,
    'visitor_name' => $testName,
    'email' => 'qa.visitor@example.com',
    'comment_body' => $xssPayload,
]);
$commentRes = $httpKernel->handle($commentReq);
assertCheck($commentRes->isRedirection(), "Comment submission accepted with redirect back");

$pendingComment = Comment::where('article_id', $firstArticle->id)
    ->where('visitor_name', $testName)
    ->where('status', 'pending')
    ->first();
assertCheck($pendingComment !== null, "New comment stored with 'pending' status");

// Verify pending comment is NOT visible on public article page
$freshArticleReq = Request::create("/news/{$firstArticle->slug}", 'GET');
$freshArticleRes = $httpKernel->handle($freshArticleReq);
assertCheck(!str_contains($freshArticleRes->getContent(), $xssPayload), "Pending comment is hidden from public view");

// Admin approves comment
if ($pendingComment) {
    $pendingComment->update(['status' => 'approved']);
    
    // Now verify approved comment IS visible and properly sanitized
    $approvedArticleReq = Request::create("/news/{$firstArticle->slug}", 'GET');
    $approvedArticleRes = $httpKernel->handle($approvedArticleReq);
    assertCheck(str_contains($approvedArticleRes->getContent(), 'Great bowling!'), "Approved comment text is visible on public article page");
    assertCheck(!str_contains($approvedArticleRes->getContent(), "<script>alert('xss-test')</script>"), "Malicious script payload neutralized, never executed in HTML");
    
    // Clean up test comment
    $pendingComment->delete();
}

// ---------------------------------------------------------------------
// TEST 4: ADMIN LOGIN & ROUTE ACCESS PROTECTION
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 4. Testing Admin Access Protection ---" . PHP_EOL;
Auth::logout();
$guestAdminReq = Request::create('/admin/dashboard', 'GET');
$guestAdminRes = $httpKernel->handle($guestAdminReq);
assertCheck($guestAdminRes->isRedirection() && str_contains($guestAdminRes->headers->get('Location'), '/admin/login'), "Guest blocked from admin dashboard and redirected to /admin/login");

$regularUser = User::where('is_admin', false)->first();
if ($regularUser) {
    Auth::login($regularUser);
    $nonAdminReq = Request::create('/admin/dashboard', 'GET');
    $nonAdminRes = $httpKernel->handle($nonAdminReq);
    assertCheck($nonAdminRes->getStatusCode() === 403, "Non-admin authenticated user receives 403 Forbidden");
    assertCheck(str_contains($nonAdminRes->getContent(), 'Restricted Pavilion Area') || str_contains($nonAdminRes->getContent(), '403'), "Custom 403 branded error page displayed");
}

$adminUser = User::where('is_admin', true)->first();
if ($adminUser) {
    Auth::login($adminUser);
    $adminReq = Request::create('/admin/dashboard', 'GET');
    $adminRes = $httpKernel->handle($adminReq);
    assertCheck($adminRes->getStatusCode() === 200, "Administrator accesses /admin/dashboard with 200 OK");
}

// ---------------------------------------------------------------------
// TEST 5: ARTICLE & CATEGORY MANAGEMENT WITH SAFEGUARDS
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 5. Testing Article & Category Management Safeguards ---" . PHP_EOL;
// Slug safety
$art1 = Article::create([
    'title' => 'QA Safety Test Article',
    'slug' => 'qa-safety-test-article',
    'category_id' => $firstCategory->id,
    'author_id' => $adminUser->id,
    'content' => 'Article content for QA testing.',
    'status' => 'draft',
]);
assertCheck($art1 && $art1->exists, "Article created in database");

// Category deletion guard
$catWithArticles = Category::has('articles')->first();
$catController = new \App\Http\Controllers\Admin\CategoryController();
$deleteGuardRes = $catController->destroy($catWithArticles);
assertCheck(Category::find($catWithArticles->id) !== null, "Category with articles cannot be deleted (relational guard intact)");

$art1->delete();

// ---------------------------------------------------------------------
// TEST 6: QUIZ CREATION & 10-QUESTION PUBLICATION GUARD
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 6. Testing Quiz 10-Question Publication Guard ---" . PHP_EOL;
$shortQuiz = Quiz::create([
    'title' => 'Under-Questioned Quiz',
    'slug' => 'under-questioned-quiz-' . time(),
    'description' => 'Quiz with fewer than 10 questions.',
    'status' => 'draft',
]);

$quizAdminController = new \App\Http\Controllers\Admin\QuizController();
$publishAttemptRes = $quizAdminController->publish($shortQuiz);
assertCheck(session('error') && str_contains(session('error'), 'Cannot publish quiz'), "Publishing quiz with < 10 questions correctly blocked by validation: " . session('error'));

$shortQuiz->delete();

// ---------------------------------------------------------------------
// TEST 7: TEN-QUESTION QUIZ GAMEPLAY & NO ANSWER LEAKS
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 7. Testing Quiz Gameplay & Answer Obfuscation ---" . PHP_EOL;
$publishedQuiz = Quiz::where('status', 'published')->first();
if ($publishedQuiz) {
    $quizzesReq = Request::create('/quizzes', 'GET');
    $quizzesRes = $httpKernel->handle($quizzesReq);
    assertCheck($quizzesRes->getStatusCode() === 200, "Public /quizzes hub returns HTTP 200 OK");

    $quizShowReq = Request::create("/quizzes/{$publishedQuiz->slug}", 'GET');
    $quizShowRes = $httpKernel->handle($quizShowReq);
    assertCheck($quizShowRes->getStatusCode() === 200, "Quiz landing page returns HTTP 200 OK");

    $quizPlayReq = Request::create("/quizzes/{$publishedQuiz->slug}/play", 'GET');
    $quizPlayRes = $httpKernel->handle($quizPlayReq);
    assertCheck($quizPlayRes->getStatusCode() === 200, "Quiz play interface returns HTTP 200 OK");

    // CRITICAL SECURITY AUDIT: Verify correct answers & explanations are NOT in the gameplay HTML
    $playContent = $quizPlayRes->getContent();
    $firstQ = $publishedQuiz->questions()->first();
    assertCheck(!str_contains($playContent, '"correct_option"'), "correct_option key NEVER leaked in gameplay JSON/HTML");
    assertCheck(!str_contains($playContent, $firstQ->explanation), "Question explanation is NOT leaked before submission");
} else {
    assertCheck(false, "No published quiz found to test gameplay");
}

// ---------------------------------------------------------------------
// TEST 8: SERVER-SIDE SCORING, TIMEOUT & IDEMPOTENCY
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 8. Testing Server-Side Scoring & Submission Controls ---" . PHP_EOL;
if ($publishedQuiz) {
    $activeAttempt = QuizAttempt::create([
        'quiz_id' => $publishedQuiz->id,
        'attempt_token' => (string) \Illuminate\Support\Str::uuid(),
        'started_at' => now(),
        'status' => 'in_progress',
        'session_id' => session()->getId(),
    ]);

    if ($activeAttempt) {
        $questions = $publishedQuiz->questions()->get();
        $sampleAnswers = [];
        $expectedScore = 0;
        
        // Answer first 3 correctly, rest wrong
        foreach ($questions as $idx => $q) {
            if ($idx < 3) {
                $sampleAnswers[$q->id] = $q->correct_option;
                $expectedScore++;
            } else {
                $sampleAnswers[$q->id] = ($q->correct_option === 'a') ? 'b' : 'a';
            }
        }

        // Ensure session has ownership
        session(['my_quiz_attempts' => [$activeAttempt->attempt_token]]);
        
        $quizController = new \App\Http\Controllers\QuizController();
        $submitReq = Request::create("/quizzes/{$publishedQuiz->slug}/submit", 'POST', [
            'attempt_token' => $activeAttempt->attempt_token,
            'answers' => $sampleAnswers,
        ]);

        $submitRes = $quizController->submit($submitReq, $publishedQuiz->slug);
        $activeAttempt->refresh();

        assertCheck($activeAttempt->isCompleted(), "Attempt marked as completed after submission");
        assertCheck($activeAttempt->score === $expectedScore, "Server correctly scored {$expectedScore}/10 without trusting client");

        // Idempotency check: submitting again
        $duplicateSubmitRes = $quizController->submit($submitReq, $publishedQuiz->slug);
        assertCheck($duplicateSubmitRes->isRedirection(), "Duplicate submission redirected safely");
    }
}

// ---------------------------------------------------------------------
// TEST 9: SEO METADATA, ROBOTS.TXT & SITEMAP
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 9. Testing SEO Metadata, Robots.txt & Sitemap ---" . PHP_EOL;
// Robots.txt
$robotsContent = file_get_contents(public_path('robots.txt'));
assertCheck(str_contains($robotsContent, 'Disallow: /admin/'), "Robots.txt disallows /admin/");
assertCheck(str_contains($robotsContent, 'Disallow: /quizzes/*/play'), "Robots.txt disallows /quizzes/*/play");
assertCheck(str_contains($robotsContent, 'Disallow: /quizzes/*/result/'), "Robots.txt disallows /quizzes/*/result/");
assertCheck(str_contains($robotsContent, 'sitemap.xml'), "Robots.txt links to sitemap.xml");

// Sitemap
$sitemapReq = Request::create('/sitemap.xml', 'GET');
$sitemapRes = $httpKernel->handle($sitemapReq);
assertCheck($sitemapRes->getStatusCode() === 200, "Sitemap /sitemap.xml returns HTTP 200 OK");
assertCheck(str_contains($sitemapRes->headers->get('Content-Type'), 'application/xml'), "Sitemap has application/xml Content-Type");
assertCheck(str_contains($sitemapRes->getContent(), '<urlset'), "Sitemap contains valid XML urlset");
assertCheck(str_contains($sitemapRes->getContent(), $firstArticle->slug), "Sitemap includes published articles");
assertCheck(str_contains($sitemapRes->getContent(), $publishedQuiz->slug), "Sitemap includes published quizzes");
assertCheck(!str_contains($sitemapRes->getContent(), '/admin'), "Sitemap DOES NOT contain admin pages");

// Robots meta tag on admin & quiz result
$adminLayoutContent = file_get_contents(resource_path('views/admin/layouts/admin.blade.php'));
assertCheck(str_contains($adminLayoutContent, '<meta name="robots" content="noindex, nofollow">'), "Admin layout includes noindex, nofollow meta tag");

$quizResultView = file_get_contents(resource_path('views/quizzes/result.blade.php'));
assertCheck(str_contains($quizResultView, "@section('robots', 'noindex, nofollow')"), "Quiz result includes noindex, nofollow directive");

// ---------------------------------------------------------------------
// TEST 10: DEPLOYMENT SETTINGS & PRODUCTION ENVIRONMENT
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 10. Testing Deployment & Environment Configuration ---" . PHP_EOL;
$appDebug = config('app.debug');
$appEnv = config('app.env');
$appUrl = config('app.url');

assertCheck($appDebug === false, "Production debug mode is disabled (APP_DEBUG=false)");
assertCheck($appEnv === 'production', "Application environment is set to production (APP_ENV=production)");
assertCheck(str_contains($appUrl, '8084'), "App URL correctly configured with port 8084: {$appUrl}");

echo PHP_EOL . "=====================================================================" . PHP_EOL;
echo "  SUMMARY: Total Tests: {$totalTests} | Passed: " . ($totalTests - $failedTests) . " | Failed: {$failedTests}" . PHP_EOL;
echo "=====================================================================" . PHP_EOL;

if ($failedTests > 0) {
    exit(1);
}
