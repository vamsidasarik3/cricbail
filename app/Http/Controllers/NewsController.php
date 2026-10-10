<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsController extends Controller
{
    /**
     * Display the paginated news listing with optional category filtering.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $selectedCategorySlug = $request->query('category');
        if ($selectedCategorySlug) {
            return redirect()->route('news.category', $selectedCategorySlug, 301);
        }

        $query = Article::published()
            ->with(['category', 'author'])
            ->latest('published_at');

        $articles = $query->paginate(6)->withQueryString();
        $categories = Category::withCount(['articles' => fn ($q) => $q->published()])->get();
        $selectedCategory = null;
        return view('news.index', compact('articles', 'categories', 'selectedCategory'));
    }

    /**
     * Display articles for a specific category slug.
     */
    public function category(string $slug): View
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $articles = $category->articles()
            ->published()
            ->with(['category', 'author'])
            ->latest('published_at')
            ->paginate(6);

        $otherCategories = Category::where('id', '!=', $category->id)->get();

        return view('news.category', compact('category', 'articles', 'otherCategories'));
    }

    /**
     * Display a single published article with related stories and comments.
     */
    public function show(string $slug): View
    {
        $article = Article::published()
            ->where('slug', $slug)
            ->with(['category', 'author', 'comments' => fn ($q) => $q->approved()->latest()])
            ->firstOrFail();

        // Related articles: same category first, excluding current article
        $relatedArticles = Article::published()
            ->where('id', '!=', $article->id)
            ->where('category_id', $article->category_id)
            ->latest('published_at')
            ->take(3)
            ->get();

        if ($relatedArticles->count() < 3) {
            $additionalCount = 3 - $relatedArticles->count();
            $existingIds = $relatedArticles->pluck('id')->push($article->id);

            $moreArticles = Article::published()
                ->whereNotIn('id', $existingIds)
                ->latest('published_at')
                ->take($additionalCount)
                ->get();

            $relatedArticles = $relatedArticles->concat($moreArticles);
        }

        return view('news.show', compact('article', 'relatedArticles'));
    }

    /**
     * Store a visitor comment for moderation on a published article.
     */
    public function storeComment(Request $request, string $slug): RedirectResponse
    {
        // Accept comments strictly for published articles; throws 404 for drafts or nonexistent articles
        $article = Article::published()->where('slug', $slug)->firstOrFail();

        // Honeypot spam protection: bots fill hidden inputs
        if ($request->filled('website_hp')) {
            return back()->with('comment_success', 'Thank you! Your comment has been submitted and is pending review by our editorial team.');
        }

        $validated = $request->validate([
            'visitor_name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'string', 'email', 'max:150'],
            'comment_body' => ['required', 'string', 'min:5', 'max:1500'],
        ], [
            'visitor_name.required' => 'Please enter your name.',
            'visitor_name.min' => 'Your name must be at least 2 characters.',
            'email.required' => 'Please provide a valid email address.',
            'email.email' => 'Please enter a valid email format.',
            'comment_body.required' => 'Please enter your comment.',
            'comment_body.min' => 'Your comment must be at least 5 characters.',
            'comment_body.max' => 'Your comment cannot exceed 1500 characters.',
        ]);

        $article->comments()->create([
            'visitor_name' => strip_tags($validated['visitor_name']),
            'email' => $validated['email'],
            'comment_body' => strip_tags($validated['comment_body']),
            'status' => 'pending',
        ]);

        return back()->with('comment_success', 'Thank you! Your comment has been submitted and is pending review by our editorial team.');
    }
}
