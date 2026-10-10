<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Quiz;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Render a dynamic XML sitemap of all published resources.
     */
    public function index(): Response
    {
        $articles = Article::published()->latest('published_at')->get();
        $categories = Category::all();
        $quizzes = Quiz::where('status', 'published')->latest('published_at')->get();

        $content = view('sitemap', compact('articles', 'categories', 'quizzes'))->render();

        return response($content, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
}
