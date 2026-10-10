<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class QuizController extends Controller
{
    /**
     * Display a listing of quizzes with question counts and status.
     */
    public function index(): View
    {
        $quizzes = Quiz::withCount(['questions', 'attempts'])
            ->latest('id')
            ->paginate(15);

        return view('admin.quizzes.index', compact('quizzes'));
    }

    /**
     * Show the form for creating a new quiz.
     */
    public function create(): View
    {
        return view('admin.quizzes.create');
    }

    /**
     * Store a newly created quiz.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200', 'unique:quizzes,slug'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['title']);

        // Check unique slug
        $originalSlug = $slug;
        $counter = 1;
        while (Quiz::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-" . $counter++;
        }

        // Publication guard: brand new quiz has 0 questions
        if ($validated['status'] === 'published') {
            return back()->withInput()->withErrors([
                'status' => 'A quiz cannot be published upon creation because it must contain exactly 10 valid questions. Save as draft first, then add questions.',
            ]);
        }

        $quiz = Quiz::create([
            'title' => $validated['title'],
            'slug' => $slug,
            'description' => $validated['description'],
            'status' => 'draft',
            'published_at' => null,
        ]);

        return redirect()->route('admin.quizzes.questions.index', $quiz->id)
            ->with('status', "Quiz \"{$quiz->title}\" created successfully as a draft. You can now add exactly 10 questions before publishing.");
    }

    /**
     * Show the form for editing the quiz details.
     */
    public function edit(Quiz $quiz): View
    {
        $quiz->loadCount(['questions', 'attempts']);
        return view('admin.quizzes.edit', compact('quiz'));
    }

    /**
     * Update the quiz details.
     */
    public function update(Request $request, Quiz $quiz): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['required', 'string', 'max:200', 'unique:quizzes,slug,' . $quiz->id],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $validated['slug'] = Str::slug($validated['slug']);

        // Prevent publication unless it has exactly 10 valid questions
        if ($validated['status'] === 'published' && $quiz->status !== 'published') {
            $errors = [];
            if (!$quiz->validateForPublication($errors)) {
                return back()->withInput()->withErrors([
                    'status' => 'Cannot publish quiz: ' . implode(' ', $errors),
                ]);
            }
            $validated['published_at'] = $quiz->published_at ?? now();
        } elseif ($validated['status'] === 'draft') {
            $validated['published_at'] = null;
        }

        $quiz->update($validated);

        return redirect()->route('admin.quizzes.index')
            ->with('status', "Quiz \"{$quiz->title}\" updated successfully.");
    }

    /**
     * Publish a quiz after verifying exactly 10 valid questions.
     */
    public function publish(Quiz $quiz): RedirectResponse
    {
        $errors = [];
        if (!$quiz->validateForPublication($errors)) {
            return back()->with('error', 'Cannot publish quiz: ' . implode(' ', $errors));
        }

        $quiz->update([
            'status' => 'published',
            'published_at' => $quiz->published_at ?? now(),
        ]);

        return back()->with('status', "Quiz \"{$quiz->title}\" has been published and is now live for public players!");
    }

    /**
     * Revert a published quiz back to draft status.
     */
    public function unpublish(Quiz $quiz): RedirectResponse
    {
        $quiz->update([
            'status' => 'draft',
            'published_at' => null,
        ]);

        return back()->with('status', "Quiz \"{$quiz->title}\" reverted to draft status.");
    }

    /**
     * Remove the specified quiz from storage.
     */
    public function destroy(Quiz $quiz): RedirectResponse
    {
        $title = $quiz->title;
        $quiz->delete();

        return redirect()->route('admin.quizzes.index')
            ->with('status', "Quiz \"{$title}\" and all associated questions were deleted.");
    }
}
