<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Quiz;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Display the public CricBail homepage.
     */
    public function index(): View
    {
        // 1. Featured marquee article
        $featuredArticle = Article::published()
            ->with(['category', 'author'])
            ->latest('published_at')
            ->first();

        // 2. Latest news stories (excluding the featured story)
        $latestArticlesQuery = Article::published()
            ->with(['category', 'author'])
            ->latest('published_at');

        if ($featuredArticle) {
            $latestArticlesQuery->where('id', '!=', $featuredArticle->id);
        }

        $latestArticles = $latestArticlesQuery->take(4)->get();

        // 3. Category spotlight sections
        $iplCategory = Category::where('slug', 'ipl')->first();
        $iplArticles = $iplCategory
            ? $iplCategory->articles()->published()->with('author')->latest('published_at')->take(3)->get()
            : collect();

        $intlCategory = Category::where('slug', 'international-cricket')->first();
        $intlArticles = $intlCategory
            ? $intlCategory->articles()->published()->with('author')->latest('published_at')->take(3)->get()
            : collect();

        $analysisCategory = Category::where('slug', 'analysis')->first();
        $analysisArticles = $analysisCategory
            ? $analysisCategory->articles()->published()->with('author')->latest('published_at')->take(3)->get()
            : collect();

        // 4. Recent playable quizzes (exactly 10 questions)
        $recentQuizzes = Quiz::playable()
            ->withCount('questions')
            ->latest('published_at')
            ->take(3)
            ->get();

        // 5. All categories for the navigation
        $allCategories = Category::orderBy('name')->get();

        return view('home', compact(
            'featuredArticle',
            'latestArticles',
            'iplCategory',
            'iplArticles',
            'intlCategory',
            'intlArticles',
            'analysisCategory',
            'analysisArticles',
            'recentQuizzes',
            'allCategories'
        ));
    }
}
