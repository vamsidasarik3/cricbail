<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class QuizController extends Controller
{
    /**
     * Display a listing of published, playable quizzes.
     */
    public function index(): View
    {
        $quizzes = Quiz::playable()
            ->withCount('questions')
            ->latest('published_at')
            ->paginate(9);

        return view('quizzes.index', compact('quizzes'));
    }

    /**
     * Display quiz briefing, rules, and Start Quiz CTA.
     */
    public function show(string $slug): View
    {
        $quiz = Quiz::playable()
            ->withCount('questions')
            ->where('slug', $slug)
            ->firstOrFail();

        return view('quizzes.show', compact('quiz'));
    }

    /**
     * Render the interactive quiz playing interface.
     * Manages attempt creation, server-side timer resumption, and client-safe payload.
     */
    public function play(string $slug): View|RedirectResponse
    {
        $quiz = Quiz::playable()
            ->where('slug', $slug)
            ->firstOrFail();

        $sessionKey = "quiz_active_attempt_{$quiz->id}";
        $activeToken = session($sessionKey);
        $attempt = null;

        if ($activeToken) {
            $attempt = QuizAttempt::where('attempt_token', $activeToken)
                ->where('quiz_id', $quiz->id)
                ->where('status', 'in_progress')
                ->first();
        }

        // If existing attempt has exceeded time limit + grace period, expire it
        if ($attempt && $attempt->hasExceededTimeLimit()) {
            $attempt->update(['status' => 'expired']);
            $attempt = null;
            session()->forget($sessionKey);
        }

        // If no active in-progress attempt exists, create a new one
        if (!$attempt) {
            $attempt = QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'attempt_token' => (string) Str::uuid(),
                'started_at' => now(),
                'status' => 'in_progress',
                'session_id' => session()->getId(),
            ]);

            session([$sessionKey => $attempt->attempt_token]);

            // Register attempt in user's session history for result access
            $myAttempts = session('my_quiz_attempts', []);
            $myAttempts[] = $attempt->attempt_token;
            session(['my_quiz_attempts' => array_unique($myAttempts)]);
        }

        $remainingSeconds = $attempt->remaining_seconds;

        // If remaining time is zero, redirect with expired notice
        if ($remainingSeconds <= 0) {
            $attempt->update(['status' => 'expired']);
            session()->forget($sessionKey);

            return redirect()->route('quizzes.show', $quiz->slug)
                ->with('error', 'Your 120-second time limit expired. Please start a fresh quiz attempt.');
        }

        // Map questions to client-safe structure: NEVER include correct_option or explanation!
        $clientQuestions = $quiz->questions()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Question $q) => $q->toClientPayload())
            ->values();

        return view('quizzes.play', [
            'quiz' => $quiz,
            'attempt' => $attempt,
            'remainingSeconds' => $remainingSeconds,
            'questions' => $clientQuestions,
            'savedAnswers' => $attempt->answers ?? [],
        ]);
    }

    /**
     * Submit and score a quiz attempt on the server.
     * Enforces server calculation, duplicate submission block, and time window validation.
     */
    public function submit(Request $request, string $slug): RedirectResponse
    {
        $validated = $request->validate([
            'attempt_token' => ['required', 'string', 'uuid'],
            'answers' => ['nullable', 'array'],
        ]);

        $quiz = Quiz::playable()->where('slug', $slug)->firstOrFail();

        $attempt = QuizAttempt::where('attempt_token', $validated['attempt_token'])
            ->where('quiz_id', $quiz->id)
            ->firstOrFail();

        // 1. Access Control: verify visitor owns this attempt
        $userAttempts = session('my_quiz_attempts', []);
        $isOwner = in_array($attempt->attempt_token, $userAttempts) 
            || $attempt->session_id === session()->getId()
            || (auth()->check() && auth()->user()->is_admin);

        if (!$isOwner) {
            abort(403, 'Unauthorized access: You do not have permission to submit this quiz attempt.');
        }

        // 2. Idempotency & Duplicate Submission Prevention
        if ($attempt->isCompleted()) {
            return redirect()->route('quizzes.result', [
                'slug' => $quiz->slug,
                'attempt' => $attempt->attempt_token
            ])->with('info', 'This quiz attempt was already scored and submitted.');
        }

        // 3. Time limit check with clearly defined server-side policy (120s + 10s grace period)
        $elapsedSeconds = max(0, now()->timestamp - $attempt->started_at->timestamp);
        $maxAllowed = QuizAttempt::TIME_LIMIT_SECONDS + QuizAttempt::GRACE_PERIOD_SECONDS;

        if ($elapsedSeconds > $maxAllowed) {
            $attempt->update([
                'status' => 'expired',
                'time_taken' => QuizAttempt::TIME_LIMIT_SECONDS,
                'completed_at' => now(),
            ]);
            session()->forget("quiz_active_attempt_{$quiz->id}");

            return redirect()->route('quizzes.result', [
                'slug' => $quiz->slug,
                'attempt' => $attempt->attempt_token
            ])->with('error', 'Your quiz submission arrived after the 120-second time limit expired.');
        }

        // 4. Server-Side Score Calculation (Never trust client scores!)
        $submittedAnswers = $validated['answers'] ?? [];
        $storedAnswers = [];
        $score = 0;

        $questions = $quiz->questions()->orderBy('display_order')->orderBy('id')->get();

        foreach ($questions as $question) {
            $userChoice = null;
            if (isset($submittedAnswers[$question->id])) {
                $rawChoice = strtolower(trim((string) $submittedAnswers[$question->id]));
                if (in_array($rawChoice, ['a', 'b', 'c', 'd'])) {
                    $userChoice = $rawChoice;
                }
            }

            $storedAnswers[$question->id] = $userChoice;

            if ($userChoice && $question->isOptionCorrect($userChoice)) {
                $score++;
            }
        }

        $timeTaken = min(QuizAttempt::TIME_LIMIT_SECONDS, max(1, $elapsedSeconds));

        // 5. Finalize attempt record
        $attempt->update([
            'score' => $score,
            'time_taken' => $timeTaken,
            'completed_at' => now(),
            'status' => 'completed',
            'answers' => $storedAnswers,
        ]);

        // Clear active session pointer
        session()->forget("quiz_active_attempt_{$quiz->id}");

        return redirect()->route('quizzes.result', [
            'slug' => $quiz->slug,
            'attempt' => $attempt->attempt_token
        ]);
    }

    /**
     * Display the comprehensive quiz result for a completed attempt.
     */
    public function result(string $slug, QuizAttempt $attempt): View
    {
        $quiz = Quiz::where('slug', $slug)->firstOrFail();

        // Enforce quiz ownership for attempt
        if ($attempt->quiz_id !== $quiz->id) {
            abort(404, 'Attempt does not belong to this quiz.');
        }

        // Enforce session-based access control for anonymous visitors
        $userAttempts = session('my_quiz_attempts', []);
        $isOwner = in_array($attempt->attempt_token, $userAttempts) 
            || $attempt->session_id === session()->getId()
            || (auth()->check() && auth()->user()->is_admin);

        if (!$isOwner) {
            abort(403, 'Unauthorized: You do not have permission to view results for this attempt.');
        }

        $questions = $quiz->questions()->orderBy('display_order')->orderBy('id')->get();
        $userAnswers = $attempt->answers ?? [];

        return view('quizzes.result', [
            'quiz' => $quiz,
            'attempt' => $attempt,
            'questions' => $questions,
            'userAnswers' => $userAnswers,
        ]);
    }
}
