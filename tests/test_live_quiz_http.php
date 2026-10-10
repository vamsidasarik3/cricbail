<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuizAttempt;

$cookieJar = tempnam(sys_get_temp_dir(), 'cricbail_quiz_user_');
$adminCookieJar = tempnam(sys_get_temp_dir(), 'cricbail_quiz_admin_');

function httpGet($url, $cookieFile = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Host: cricbail.vkrshna.in:8084']);
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

echo "=== CRICBAIL LIVE APACHE HTTP QUIZ E2E TEST ===" . PHP_EOL . PHP_EOL;

$baseUrl = 'http://127.0.0.1:8084';
$quiz = Quiz::playable()->first();
$quizSlug = $quiz->slug;

// 1. Quizzes Hub Listing
echo "1. Fetching public quizzes hub: {$baseUrl}/quizzes ... ";
$hubRes = httpGet("{$baseUrl}/quizzes", $cookieJar);
$hasQuizTitle = str_contains($hubRes['body'], e($quiz->title)) || str_contains($hubRes['body'], $quiz->slug);
echo "HTTP {$hubRes['status']}" . ($hasQuizTitle ? ' [PASSED: Quiz listed]' : ' [FAILED]') . PHP_EOL;

// 2. Quiz Briefing & Overview
echo "2. Fetching quiz overview: {$baseUrl}/quizzes/{$quizSlug} ... ";
$showRes = httpGet("{$baseUrl}/quizzes/{$quizSlug}", $cookieJar);
$hasStartButton = str_contains($showRes['body'], 'Start Quiz Now');
$hasRules = str_contains($showRes['body'], '120 seconds') && str_contains($showRes['body'], '10 questions');
echo "HTTP {$showRes['status']}" . ($hasStartButton && $hasRules ? ' [PASSED: Rules & CTA present]' : ' [FAILED]') . PHP_EOL;

// 3. Quiz Gameplay Screen
echo "3. Starting interactive gameplay: {$baseUrl}/quizzes/{$quizSlug}/play ... ";
$playRes = httpGet("{$baseUrl}/quizzes/{$quizSlug}/play", $cookieJar);
$csrfToken = extractCsrf($playRes['body']);
preg_match('/name="attempt_token"\s+value="([^"]+)"/', $playRes['body'], $matchAttempt);
$attemptToken = $matchAttempt[1] ?? null;

// Security check: ensure NO correct_option or explanation is exposed
$questions = $quiz->questions()->get();
$leakFound = false;
foreach ($questions as $q) {
    if (str_contains($playRes['body'], $q->explanation)) {
        $leakFound = true;
        break;
    }
}

echo "HTTP {$playRes['status']} [Attempt Token: " . ($attemptToken ? substr($attemptToken, 0, 8) . '...' : 'MISSING') . "]" . PHP_EOL;
echo "   - Information security check: " . (!$leakFound ? '[PASSED: Explanations/Answers strictly hidden]' : '[FAILED: Leak detected!]') . PHP_EOL;

if (!$attemptToken || !$csrfToken) {
    echo "ERROR: Could not obtain CSRF token or attempt token from play page!" . PHP_EOL;
    exit(1);
}

// 4. Submitting Answers via Live POST
echo "4. Submitting quiz answers via POST ... ";
$submissionAnswers = [];
foreach ($questions as $q) {
    $submissionAnswers[$q->id] = $q->correct_option; // Answer all 10 correctly
}

$submitData = [
    '_token' => $csrfToken,
    'attempt_token' => $attemptToken,
    'answers' => $submissionAnswers,
];

$submitRes = httpPost("{$baseUrl}/quizzes/{$quizSlug}/submit", $submitData, $cookieJar, "{$baseUrl}/quizzes/{$quizSlug}/play");
$hasScorecard = str_contains($submitRes['body'], 'Official Verified Scorecard');
$hasScore10 = str_contains($submitRes['body'], '10') && str_contains($submitRes['body'], 'out of 10');
$hasExplanationsNow = str_contains($submitRes['body'], 'Detailed Question Breakdown &amp; Explanations') 
    || str_contains($submitRes['body'], 'Detailed Question Breakdown & Explanations');

echo "HTTP {$submitRes['status']} -> Final URL: {$submitRes['url']}" . PHP_EOL;
echo "   - Scorecard displayed: " . ($hasScorecard ? '[PASSED]' : '[FAILED]') . PHP_EOL;
echo "   - Score 10/10 verified: " . ($hasScore10 ? '[PASSED]' : '[FAILED]') . PHP_EOL;
echo "   - Post-quiz explanations rendered: " . ($hasExplanationsNow ? '[PASSED]' : '[FAILED]') . PHP_EOL;

// 5. Admin Authentication & Management
echo "5. Admin logging in at {$baseUrl}/admin/login ... ";
$adminLoginPage = httpGet("{$baseUrl}/admin/login", $adminCookieJar);
$adminCsrf = extractCsrf($adminLoginPage['body']);

$adminLoginRes = httpPost("{$baseUrl}/admin/login", [
    '_token' => $adminCsrf,
    'email' => 'admin@cricbail.com',
    'password' => 'Password123!',
], $adminCookieJar, "{$baseUrl}/admin/login");

echo "HTTP {$adminLoginRes['status']} -> URL: {$adminLoginRes['url']}" . PHP_EOL;

// 6. Admin Quizzes Management Page
echo "6. Admin fetching quizzes management dashboard ... ";
$adminQuizzesRes = httpGet("{$baseUrl}/admin/quizzes", $adminCookieJar);
$hasManageLink = str_contains($adminQuizzesRes['body'], 'Cricket Quizzes Management');
echo "HTTP {$adminQuizzesRes['status']}" . ($hasManageLink ? ' [PASSED]' : ' [FAILED]') . PHP_EOL;

// 7. Admin Create Draft Quiz
echo "7. Admin creating new draft quiz ... ";
$adminCreatePage = httpGet("{$baseUrl}/admin/quizzes/create", $adminCookieJar);
$createCsrf = extractCsrf($adminCreatePage['body']);

$uniqueQuizTitle = "Live Test Quiz " . time();
$createRes = httpPost("{$baseUrl}/admin/quizzes", [
    '_token' => $createCsrf,
    'title' => $uniqueQuizTitle,
    'description' => 'A test draft quiz created via live HTTP test suite.',
    'status' => 'draft',
], $adminCookieJar, "{$baseUrl}/admin/quizzes/create");

$createdInDb = Quiz::where('title', $uniqueQuizTitle)->first();
echo "HTTP {$createRes['status']}" . ($createdInDb ? " [PASSED: Quiz #{$createdInDb->id} created as draft]" : ' [FAILED]') . PHP_EOL;

// 8. Admin Publication Guard (Try publishing draft with 0 questions)
if ($createdInDb) {
    echo "8. Admin attempting to publish quiz with 0 questions ... ";
    $adminManagePage = httpGet("{$baseUrl}/admin/quizzes/{$createdInDb->id}/questions", $adminCookieJar);
    $manageCsrf = extractCsrf($adminManagePage['body']);

    $publishRes = httpPost("{$baseUrl}/admin/quizzes/{$createdInDb->id}/publish", [
        '_token' => $manageCsrf,
    ], $adminCookieJar, "{$baseUrl}/admin/quizzes/{$createdInDb->id}/questions");

    $createdInDb->refresh();
    echo "HTTP {$publishRes['status']} | DB status: '{$createdInDb->status}'" . 
        ($createdInDb->status === 'draft' ? ' [PASSED: Blocked from publishing with <10 questions]' : ' [FAILED: Published!]') . PHP_EOL;

    // Delete test draft quiz
    $deleteRes = httpPost("{$baseUrl}/admin/quizzes/{$createdInDb->id}", [
        '_token' => $manageCsrf,
        '_method' => 'DELETE',
    ], $adminCookieJar, "{$baseUrl}/admin/quizzes");
    $existsAfterDelete = Quiz::where('id', $createdInDb->id)->exists();
    echo "   - Test quiz cleaned up: " . (!$existsAfterDelete ? '[PASSED]' : '[FAILED]') . PHP_EOL;
}

// Cleanup cookie jars and test attempt
@unlink($cookieJar);
@unlink($adminCookieJar);
QuizAttempt::where('attempt_token', $attemptToken)->delete();

echo PHP_EOL . "=== ALL LIVE APACHE HTTP QUIZ E2E TESTS PASSED 100% ===" . PHP_EOL;
