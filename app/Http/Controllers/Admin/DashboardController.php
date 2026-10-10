<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the administrator dashboard with summary metrics and recent activity.
     */
    public function index(): View
    {
        $metrics = [
            'published_articles' => Article::published()->count(),
            'draft_articles' => Article::where('status', 'draft')->count(),
            'categories_count' => Category::count(),
            'pending_comments' => Comment::pending()->count(),
            'published_quizzes' => Quiz::published()->count(),
            'quiz_attempts_count' => QuizAttempt::count(),
        ];

        $recentArticles = Article::with('category')->latest('id')->take(5)->get();
        $recentComments = Comment::with('article')->latest('id')->take(5)->get();
        $recentAttempts = QuizAttempt::with('quiz')->latest('id')->take(5)->get();

        return view('admin.dashboard', compact('metrics', 'recentArticles', 'recentComments', 'recentAttempts'));
    }
}
