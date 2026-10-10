<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionController extends Controller
{
    /**
     * Display questions list for a quiz with creation/edit forms.
     */
    public function index(Quiz $quiz): View
    {
        $questions = $quiz->questions()->orderBy('display_order')->orderBy('id')->get();
        return view('admin.quizzes.questions.index', compact('quiz', 'questions'));
    }

    /**
     * Store a newly created question in the quiz.
     */
    public function store(Request $request, Quiz $quiz): RedirectResponse
    {
        $validated = $request->validate([
            'question_text' => ['required', 'string', 'max:2000'],
            'option_a' => ['required', 'string', 'max:255'],
            'option_b' => ['required', 'string', 'max:255'],
            'option_c' => ['required', 'string', 'max:255'],
            'option_d' => ['required', 'string', 'max:255'],
            'correct_option' => ['required', 'in:a,b,c,d'],
            'explanation' => ['nullable', 'string', 'max:2000'],
            'display_order' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if (empty($validated['display_order'])) {
            $maxOrder = $quiz->questions()->max('display_order') ?? 0;
            $validated['display_order'] = $maxOrder + 1;
        }

        $validated['correct_option'] = strtolower($validated['correct_option']);

        $quiz->questions()->create($validated);

        return back()->with('status', 'Question added successfully.');
    }

    /**
     * Update an existing question.
     */
    public function update(Request $request, Quiz $quiz, Question $question): RedirectResponse
    {
        if ($question->quiz_id !== $quiz->id) {
            abort(404);
        }

        $validated = $request->validate([
            'question_text' => ['required', 'string', 'max:2000'],
            'option_a' => ['required', 'string', 'max:255'],
            'option_b' => ['required', 'string', 'max:255'],
            'option_c' => ['required', 'string', 'max:255'],
            'option_d' => ['required', 'string', 'max:255'],
            'correct_option' => ['required', 'in:a,b,c,d'],
            'explanation' => ['nullable', 'string', 'max:2000'],
            'display_order' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $validated['correct_option'] = strtolower($validated['correct_option']);

        $question->update($validated);

        return back()->with('status', "Question #{$question->display_order} updated successfully.");
    }

    /**
     * Delete a question from the quiz.
     */
    public function destroy(Quiz $quiz, Question $question): RedirectResponse
    {
        if ($question->quiz_id !== $quiz->id) {
            abort(404);
        }

        $question->delete();

        // If quiz was published and now has fewer than 10 questions, unpublish it automatically!
        if ($quiz->status === 'published' && $quiz->questions()->count() < Quiz::REQUIRED_QUESTION_COUNT) {
            $quiz->update(['status' => 'draft', 'published_at' => null]);
            return back()->with('warning', 'Question deleted. Note: Since question count is now less than 10, the quiz was automatically set back to draft.');
        }

        return back()->with('status', 'Question deleted successfully.');
    }

    /**
     * Reorder questions in the quiz.
     */
    public function reorder(Request $request, Quiz $quiz): RedirectResponse
    {
        $validated = $request->validate([
            'orders' => ['required', 'array'],
            'orders.*' => ['required', 'integer', 'min:1'],
        ]);

        foreach ($validated['orders'] as $questionId => $order) {
            $quiz->questions()->where('id', $questionId)->update(['display_order' => $order]);
        }

        return back()->with('status', 'Questions reordered successfully.');
    }
}
