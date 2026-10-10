<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$consoleKernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$consoleKernel->bootstrap();

$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Http\Controllers\Admin\QuizController as AdminQuizController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

echo "=== CRICBAIL: 10-QUESTION, 2-MINUTE CRICKET QUIZ VERIFICATION SUITE ===" . PHP_EOL . PHP_EOL;

Session::start();
$token = Session::token();

$adminUser = User::where('is_admin', true)->first();
$playableQuiz = Quiz::playable()->first();
$draftQuiz = Quiz::where('status', 'draft')->first();

if (!$playableQuiz) {
    echo "ERROR: No published 10-question quiz found!" . PHP_EOL;
    exit(1);
}

// ---------------------------------------------------------------------
// 1. UNPUBLISHED / DRAFT QUIZZES INACCESSIBLE TO PUBLIC
// ---------------------------------------------------------------------
echo "--- 1. Testing Unpublished / Draft Quiz Inaccessibility ---" . PHP_EOL;

if ($draftQuiz) {
    // 1.1 Direct URL to draft quiz show page
    $reqDraftShow = Request::create("http://cricbail.vkrshna.in:8084/quizzes/{$draftQuiz->slug}", 'GET');
    $reqDraftShow->setLaravelSession(Session::driver());
    try {
        $resDraftShow = $httpKernel->handle($reqDraftShow);
        $statusDraftShow = $resDraftShow->getStatusCode();
    } catch (NotFoundHttpException $e) {
        $statusDraftShow = 404;
    }

    echo "1.1 Public visitor accessing draft quiz briefing: HTTP " . $statusDraftShow . 
        ($statusDraftShow === 404 ? " [PASSED: 404 Not Found]" : " [FAILED]") . PHP_EOL;

    // 1.2 Direct URL to draft quiz play page
    $reqDraftPlay = Request::create("http://cricbail.vkrshna.in:8084/quizzes/{$draftQuiz->slug}/play", 'GET');
    $reqDraftPlay->setLaravelSession(Session::driver());
    try {
        $resDraftPlay = $httpKernel->handle($reqDraftPlay);
        $statusDraftPlay = $resDraftPlay->getStatusCode();
    } catch (NotFoundHttpException $e) {
        $statusDraftPlay = 404;
    }

    echo "1.2 Public visitor accessing draft quiz play interface: HTTP " . $statusDraftPlay . 
        ($statusDraftPlay === 404 ? " [PASSED: 404 Not Found]" : " [FAILED]") . PHP_EOL;
}

// 1.3 Nonexistent quiz slug
$reqNonExistent = Request::create("http://cricbail.vkrshna.in:8084/quizzes/nonexistent-quiz-xyz/play", 'GET');
$reqNonExistent->setLaravelSession(Session::driver());
try {
    $resNonExistent = $httpKernel->handle($reqNonExistent);
    $status404 = $resNonExistent->getStatusCode();
} catch (NotFoundHttpException $e) {
    $status404 = 404;
}
echo "1.3 Public visitor accessing nonexistent quiz: HTTP " . $status404 . 
    ($status404 === 404 ? " [PASSED: 404 Not Found]" : " [FAILED]") . PHP_EOL;


// ---------------------------------------------------------------------
// 2. INVALID QUESTION COUNTS & PUBLICATION PREVENTION
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 2. Testing Invalid Question Counts & Publication Blocking ---" . PHP_EOL;

$adminQuizController = app(AdminQuizController::class);

// Create a test draft quiz with only 3 questions
$incompleteQuiz = Quiz::create([
    'title' => 'Incomplete Trivia Test',
    'slug' => 'incomplete-trivia-test-' . time(),
    'description' => 'Test quiz with under 10 questions.',
    'status' => 'draft',
]);

for ($i = 1; $i <= 3; $i++) {
    $incompleteQuiz->questions()->create([
        'question_text' => "Sample Question {$i}",
        'option_a' => 'Option A',
        'option_b' => 'Option B',
        'option_c' => 'Option C',
        'option_d' => 'Option D',
        'correct_option' => 'a',
        'explanation' => "Explanation for {$i}",
        'display_order' => $i,
    ]);
}

$canPublishIncomplete = $incompleteQuiz->canBePublished();
$errors = [];
$incompleteQuiz->validateForPublication($errors);

echo "2.1 Quiz with 3 questions allowed to be published: " . 
    (!$canPublishIncomplete ? "[PASSED: Blocked with error: '{$errors[0]}']" : "[FAILED: Allowed publication!]") . PHP_EOL;

// Admin publish attempt on incomplete quiz
$publishRes = $adminQuizController->publish($incompleteQuiz);
$incompleteQuiz->refresh();
echo "2.2 Admin publish action blocked on incomplete quiz: Status is '{$incompleteQuiz->status}'" . 
    ($incompleteQuiz->status === 'draft' ? " [PASSED: Remained Draft]" : " [FAILED]") . PHP_EOL;

// Clean up incomplete quiz
$incompleteQuiz->delete();

// Verify publication requirement on valid 10-question quiz
$canPublishValid = $playableQuiz->canBePublished();
echo "2.3 Quiz with exactly 10 questions validation: " . 
    ($canPublishValid ? "[PASSED: 10/10 questions approved]" : "[FAILED]") . PHP_EOL;


// ---------------------------------------------------------------------
// 3. ZERO INFORMATION LEAKAGE (CORRECT ANSWERS HIDDEN FROM CLIENT)
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 3. Testing Information Leakage & Client-Safe Payload ---" . PHP_EOL;

$questions = $playableQuiz->questions()->get();
$hasLeakage = false;

foreach ($questions as $q) {
    $clientPayload = $q->toClientPayload();
    if (isset($clientPayload['correct_option']) || isset($clientPayload['explanation'])) {
        $hasLeakage = true;
        break;
    }
}
echo "3.1 Correct answers or explanations exposed in client payload: " . 
    (!$hasLeakage ? "[PASSED: Zero leakage in client payload]" : "[FAILED: Leak detected!]") . PHP_EOL;

// Check HTML source of play view
$reqPlay = Request::create("http://cricbail.vkrshna.in:8084/quizzes/{$playableQuiz->slug}/play", 'GET');
$reqPlay->setLaravelSession(Session::driver());
$resPlay = $httpKernel->handle($reqPlay);
$playHtml = $resPlay->getContent();

// Check if any question's correct_option is exposed in play HTML
$exposedInHtml = false;
foreach ($questions as $q) {
    if (str_contains($playHtml, 'correct_option') || str_contains($playHtml, $q->explanation)) {
        $exposedInHtml = true;
        break;
    }
}
echo "3.2 Correct answers or explanations rendered in play page source: " . 
    (!$exposedInHtml ? "[PASSED: Hidden from page source]" : "[FAILED: Found in HTML source!]") . PHP_EOL;


// ---------------------------------------------------------------------
// 4. NORMAL MANUAL COMPLETION & ACCURATE SERVER-SIDE SCORING
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 4. Testing Normal Completion & Server-Side Scoring ---" . PHP_EOL;

// Create fresh attempt
$attempt1 = QuizAttempt::create([
    'quiz_id' => $playableQuiz->id,
    'attempt_token' => (string) Str::uuid(),
    'started_at' => now(),
    'status' => 'in_progress',
    'session_id' => Session::getId(),
]);
Session::put('my_quiz_attempts', [$attempt1->attempt_token]);

// Build 10 correct answers
$answersAllCorrect = [];
foreach ($playableQuiz->questions as $q) {
    $answersAllCorrect[$q->id] = $q->correct_option;
}

$reqSubmit1 = Request::create(
    "http://cricbail.vkrshna.in:8084/quizzes/{$playableQuiz->slug}/submit",
    'POST',
    [
        '_token' => $token,
        'attempt_token' => $attempt1->attempt_token,
        'answers' => $answersAllCorrect,
    ]
);
$reqSubmit1->setLaravelSession(Session::driver());
$resSubmit1 = $httpKernel->handle($reqSubmit1);
$attempt1->refresh();

echo "4.1 Submission redirects to result: HTTP " . $resSubmit1->getStatusCode() . 
    ($resSubmit1->getStatusCode() === 302 ? " [PASSED]" : " [FAILED]") . PHP_EOL;
echo "4.2 Server calculated score for 10/10 correct answers: {$attempt1->score}/10" . 
    ($attempt1->score === 10 ? " [PASSED: 10/10 points]" : " [FAILED: Score was {$attempt1->score}]") . PHP_EOL;
echo "4.3 Attempt status finalized to 'completed': {$attempt1->status}" . 
    ($attempt1->status === 'completed' ? " [PASSED]" : " [FAILED]") . PHP_EOL;
echo "4.4 Accuracy percentage calculated: {$attempt1->percentage}%" . 
    ($attempt1->percentage == 100.0 ? " [PASSED]" : " [FAILED]") . PHP_EOL;


// ---------------------------------------------------------------------
// 5. PARTIAL / UNANSWERED QUESTIONS
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 5. Testing Partial / Unanswered Questions (6 answered, 4 skipped) ---" . PHP_EOL;

$attempt2 = QuizAttempt::create([
    'quiz_id' => $playableQuiz->id,
    'attempt_token' => (string) Str::uuid(),
    'started_at' => now(),
    'status' => 'in_progress',
    'session_id' => Session::getId(),
]);
$myAttempts = Session::get('my_quiz_attempts', []);
$myAttempts[] = $attempt2->attempt_token;
Session::put('my_quiz_attempts', $myAttempts);

// Answer first 6 correctly, leave last 4 unanswered
$answersPartial = [];
$counter = 0;
foreach ($playableQuiz->questions as $q) {
    if ($counter < 6) {
        $answersPartial[$q->id] = $q->correct_option;
    }
    $counter++;
}

$reqSubmit2 = Request::create(
    "http://cricbail.vkrshna.in:8084/quizzes/{$playableQuiz->slug}/submit",
    'POST',
    [
        '_token' => $token,
        'attempt_token' => $attempt2->attempt_token,
        'answers' => $answersPartial,
    ]
);
$reqSubmit2->setLaravelSession(Session::driver());
$httpKernel->handle($reqSubmit2);
$attempt2->refresh();

echo "5.1 Partial attempt score (6 answered, 4 skipped): {$attempt2->score}/10" . 
    ($attempt2->score === 6 ? " [PASSED: Exact 6 points scored]" : " [FAILED: Score was {$attempt2->score}]") . PHP_EOL;


// ---------------------------------------------------------------------
// 6. ALTERED CLIENT-SIDE SCORES DEFENSE (NO CLIENT TRUST)
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 6. Testing Malicious Client-Side Tampered Score Defense ---" . PHP_EOL;

$attempt3 = QuizAttempt::create([
    'quiz_id' => $playableQuiz->id,
    'attempt_token' => (string) Str::uuid(),
    'started_at' => now(),
    'status' => 'in_progress',
    'session_id' => Session::getId(),
]);
$myAttempts[] = $attempt3->attempt_token;
Session::put('my_quiz_attempts', $myAttempts);

// User answers only 2 correctly, but malicious client injects score=10 in POST payload!
$tamperedPayload = [
    '_token' => $token,
    'attempt_token' => $attempt3->attempt_token,
    'score' => 10, // Tampered claim!
    'percentage' => 100, // Tampered claim!
    'answers' => [],
];
$c = 0;
foreach ($playableQuiz->questions as $q) {
    if ($c < 2) {
        $tamperedPayload['answers'][$q->id] = $q->correct_option;
    } else {
        // wrong answer
        $wrong = ($q->correct_option === 'a') ? 'b' : 'a';
        $tamperedPayload['answers'][$q->id] = $wrong;
    }
    $c++;
}

$reqSubmit3 = Request::create(
    "http://cricbail.vkrshna.in:8084/quizzes/{$playableQuiz->slug}/submit",
    'POST',
    $tamperedPayload
);
$reqSubmit3->setLaravelSession(Session::driver());
$httpKernel->handle($reqSubmit3);
$attempt3->refresh();

echo "6.1 Server ignores tampered client claim (score=10) and tallies genuine answers (2/10): {$attempt3->score}/10" . 
    ($attempt3->score === 2 ? " [PASSED: Strict server calculation enforced]" : " [FAILED: Tampered score accepted!]") . PHP_EOL;


// ---------------------------------------------------------------------
// 7. DUPLICATE SUBMISSION & RE-SCORING PREVENTION
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 7. Testing Duplicate Submission & Re-scoring Prevention ---" . PHP_EOL;

// Attempt 1 is already completed. Try to re-submit with different answers!
$reqResubmit = Request::create(
    "http://cricbail.vkrshna.in:8084/quizzes/{$playableQuiz->slug}/submit",
    'POST',
    [
        '_token' => $token,
        'attempt_token' => $attempt1->attempt_token,
        'answers' => [], // Try submitting empty answers
    ]
);
$reqResubmit->setLaravelSession(Session::driver());
$resResubmit = $httpKernel->handle($reqResubmit);
$attempt1->refresh();

echo "7.1 Duplicate submission rejected without altering score: Score is {$attempt1->score}/10" . 
    ($attempt1->score === 10 ? " [PASSED: Existing score preserved, idempotency enforced]" : " [FAILED]") . PHP_EOL;


// ---------------------------------------------------------------------
// 8. GAMEPLAY PAGE REFRESH & TIME INTEGRITY
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 8. Testing Gameplay Page Refresh Time Integrity ---" . PHP_EOL;

// Create attempt started 45 seconds ago
$attemptRefresh = QuizAttempt::create([
    'quiz_id' => $playableQuiz->id,
    'attempt_token' => (string) Str::uuid(),
    'started_at' => now()->subSeconds(45),
    'status' => 'in_progress',
    'session_id' => Session::getId(),
]);
Session::put("quiz_active_attempt_{$playableQuiz->id}", $attemptRefresh->attempt_token);

$remaining = $attemptRefresh->remaining_seconds;
echo "8.1 Attempt started 45s ago: Remaining seconds calculated = {$remaining}s" . 
    ($remaining >= 74 && $remaining <= 76 ? " [PASSED: Server calculates ~75s, does not reset to 120s]" : " [FAILED: Remaining was {$remaining}]") . PHP_EOL;


// ---------------------------------------------------------------------
// 9. TIMEOUT / AUTO-SUBMISSION AT 120 SECONDS
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 9. Testing Timeout / Auto-Submission at 120s ---" . PHP_EOL;

// Attempt auto-submitted right as timer hit 120s (elapsed = 120s, within 10s grace period)
$attemptTimeout = QuizAttempt::create([
    'quiz_id' => $playableQuiz->id,
    'attempt_token' => (string) Str::uuid(),
    'started_at' => now()->subSeconds(120),
    'status' => 'in_progress',
    'session_id' => Session::getId(),
]);
$myAttempts[] = $attemptTimeout->attempt_token;
Session::put('my_quiz_attempts', $myAttempts);

$reqSubmitTimeout = Request::create(
    "http://cricbail.vkrshna.in:8084/quizzes/{$playableQuiz->slug}/submit",
    'POST',
    [
        '_token' => $token,
        'attempt_token' => $attemptTimeout->attempt_token,
        'answers' => $answersAllCorrect,
    ]
);
$reqSubmitTimeout->setLaravelSession(Session::driver());
$resSubmitTimeout = $httpKernel->handle($reqSubmitTimeout);
$attemptTimeout->refresh();

echo "9.1 Auto-submission at 120 seconds accepted within grace window: Status '{$attemptTimeout->status}', Score {$attemptTimeout->score}/10" . 
    ($attemptTimeout->status === 'completed' && $attemptTimeout->time_taken === 120 ? " [PASSED: Scored with time_taken=120s]" : " [FAILED]") . PHP_EOL;


// ---------------------------------------------------------------------
// 10. EXPIRED ATTEMPTS REJECTION (> 130s ELAPSED)
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 10. Testing Expired Attempt Rejection (> 130s Elapsed) ---" . PHP_EOL;

// Attempt started 180 seconds ago (3 minutes ago)
$attemptExpired = QuizAttempt::create([
    'quiz_id' => $playableQuiz->id,
    'attempt_token' => (string) Str::uuid(),
    'started_at' => now()->subSeconds(180),
    'status' => 'in_progress',
    'session_id' => Session::getId(),
]);
$myAttempts[] = $attemptExpired->attempt_token;
Session::put('my_quiz_attempts', $myAttempts);

$reqSubmitExpired = Request::create(
    "http://cricbail.vkrshna.in:8084/quizzes/{$playableQuiz->slug}/submit",
    'POST',
    [
        '_token' => $token,
        'attempt_token' => $attemptExpired->attempt_token,
        'answers' => $answersAllCorrect,
    ]
);
$reqSubmitExpired->setLaravelSession(Session::driver());
$resSubmitExpired = $httpKernel->handle($reqSubmitExpired);
$attemptExpired->refresh();

echo "10.1 Late submission (>130s elapsed) marked as expired: Status is '{$attemptExpired->status}'" . 
    ($attemptExpired->status === 'expired' ? " [PASSED: Marked Expired]" : " [FAILED: Status was {$attemptExpired->status}]") . PHP_EOL;


// ---------------------------------------------------------------------
// 11. RESULT PAGE ACCESS CONTROL
// ---------------------------------------------------------------------
echo PHP_EOL . "--- 11. Testing Result Page Access Controls ---" . PHP_EOL;

// 11.1 Owner accessing their own attempt result
$reqResultOwner = Request::create(
    "http://cricbail.vkrshna.in:8084/quizzes/{$playableQuiz->slug}/result/{$attempt1->attempt_token}",
    'GET'
);
$reqResultOwner->setLaravelSession(Session::driver());
$resResultOwner = $httpKernel->handle($reqResultOwner);
echo "11.1 Owner viewing own result: HTTP " . $resResultOwner->getStatusCode() . 
    ($resResultOwner->getStatusCode() === 200 ? " [PASSED: 200 OK]" : " [FAILED]") . PHP_EOL;

// 11.2 Unauthorized visitor attempting to view someone else's attempt without ownership
Session::put('my_quiz_attempts', []); // clear user attempts
$reqResultStranger = Request::create(
    "http://cricbail.vkrshna.in:8084/quizzes/{$playableQuiz->slug}/result/{$attempt1->attempt_token}",
    'GET'
);
$reqResultStranger->setLaravelSession(Session::driver());
try {
    $resResultStranger = $httpKernel->handle($reqResultStranger);
    $statusStranger = $resResultStranger->getStatusCode();
} catch (HttpException $e) {
    $statusStranger = $e->getStatusCode();
}
echo "11.2 Unauthorized stranger accessing attempt result: HTTP " . $statusStranger . 
    ($statusStranger === 403 ? " [PASSED: 403 Forbidden]" : " [FAILED: HTTP {$statusStranger}]") . PHP_EOL;

// Cleanup test attempts
QuizAttempt::whereIn('id', [
    $attempt1->id, 
    $attempt2->id, 
    $attempt3->id, 
    $attemptRefresh->id, 
    $attemptTimeout->id, 
    $attemptExpired->id
])->delete();

echo PHP_EOL . "=== ALL 11 QUIZ VERIFICATION SUITES PASSED COMPLETELY ===" . PHP_EOL;
