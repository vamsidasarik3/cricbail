<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ArticleController extends Controller
{
    /**
     * Display a paginated listing of articles with search and category filtering.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $categoryId = $request->query('category_id');
        $status = $request->query('status');

        $query = Article::with(['category', 'author'])->withCount('comments');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($status && in_array($status, ['published', 'draft'])) {
            $query->where('status', $status);
        }

        $articles = $query->latest('id')->paginate(15)->withQueryString();
        $categories = Category::orderBy('name')->get();

        $counts = [
            'all' => Article::count(),
            'published' => Article::where('status', 'published')->count(),
            'draft' => Article::where('status', 'draft')->count(),
        ];

        return view('admin.articles.index', compact('articles', 'categories', 'counts', 'search', 'categoryId', 'status'));
    }

    /**
     * Show the form for creating a new article.
     */
    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.articles.create', compact('categories'));
    }

    /**
     * Store a newly created article in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:articles,slug'],
            'category_id' => ['required', 'exists:categories,id'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'status' => ['required', 'in:draft,published'],
            'published_at' => ['nullable', 'date'],
            'featured_image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'meta_title' => ['nullable', 'string', 'max:100'],
            'meta_description' => ['nullable', 'string', 'max:255'],
        ], [
            'category_id.required' => 'Please select a valid cricket news category.',
            'featured_image.image' => 'The featured image must be a valid image file.',
            'featured_image.mimes' => 'The image must be of type JPEG, PNG, JPG, or WEBP.',
            'featured_image.max' => 'The featured image must not exceed 2MB in size.',
        ]);

        // Secure slug generation
        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['title']);
        $originalSlug = $slug;
        $counter = 1;
        while (Article::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-" . $counter++;
        }
        $validated['slug'] = $slug;

        // Secure featured image upload handling
        if ($request->hasFile('featured_image')) {
            $imageFile = $request->file('featured_image');
            $storedPath = $imageFile->store('articles', 'public');
            $validated['featured_image'] = 'storage/' . $storedPath;
        }

        // Set publication date
        if ($validated['status'] === 'published') {
            $validated['published_at'] = !empty($validated['published_at']) 
                ? $validated['published_at'] 
                : now();
        } else {
            $validated['published_at'] = null;
        }

        $validated['author_id'] = auth()->id();

        $article = Article::create($validated);

        return redirect()->route('admin.articles.index')
            ->with('status', "Article \"{$article->title}\" created successfully.");
    }

    /**
     * Show the form for editing the specified article.
     */
    public function edit(Article $article): View
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.articles.edit', compact('article', 'categories'));
    }

    /**
     * Update the specified article in storage.
     */
    public function update(Request $request, Article $article): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:articles,slug,' . $article->id],
            'category_id' => ['required', 'exists:categories,id'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'status' => ['required', 'in:draft,published'],
            'published_at' => ['nullable', 'date'],
            'featured_image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'meta_title' => ['nullable', 'string', 'max:100'],
            'meta_description' => ['nullable', 'string', 'max:255'],
        ], [
            'category_id.required' => 'Please select a valid cricket news category.',
            'featured_image.image' => 'The featured image must be a valid image file.',
            'featured_image.mimes' => 'The image must be of type JPEG, PNG, JPG, or WEBP.',
            'featured_image.max' => 'The featured image must not exceed 2MB in size.',
        ]);

        $validated['slug'] = Str::slug($validated['slug']);

        // Secure replacement image upload
        if ($request->hasFile('featured_image')) {
            $imageFile = $request->file('featured_image');
            $storedPath = $imageFile->store('articles', 'public');
            $validated['featured_image'] = 'storage/' . $storedPath;

            // Remove previous image from storage if stored in articles
            if ($article->featured_image && str_starts_with($article->featured_image, 'storage/articles/')) {
                $oldDiskPath = str_replace('storage/', '', $article->featured_image);
                Storage::disk('public')->delete($oldDiskPath);
            }
        }

        // Publication date management
        if ($validated['status'] === 'published') {
            $validated['published_at'] = !empty($validated['published_at']) 
                ? $validated['published_at'] 
                : ($article->published_at ?? now());
        } else {
            $validated['published_at'] = null;
        }

        $article->update($validated);

        return redirect()->route('admin.articles.index')
            ->with('status', "Article \"{$article->title}\" updated successfully.");
    }

    /**
     * Publish a draft article.
     */
    public function publish(Article $article): RedirectResponse
    {
        $article->update([
            'status' => 'published',
            'published_at' => $article->published_at ?? now(),
        ]);

        return back()->with('status', "Article \"{$article->title}\" has been published.");
    }

    /**
     * Unpublish an article, reverting it to draft.
     */
    public function unpublish(Article $article): RedirectResponse
    {
        $article->update([
            'status' => 'draft',
            'published_at' => null,
        ]);

        return back()->with('status', "Article \"{$article->title}\" has been reverted to draft.");
    }

    /**
     * Remove the specified article from storage.
     */
    public function destroy(Article $article): RedirectResponse
    {
        $title = $article->title;

        // Delete uploaded featured image if stored in public storage
        if ($article->featured_image && str_starts_with($article->featured_image, 'storage/articles/')) {
            $oldDiskPath = str_replace('storage/', '', $article->featured_image);
            Storage::disk('public')->delete($oldDiskPath);
        }

        $article->delete();

        return redirect()->route('admin.articles.index')
            ->with('status', "Article \"{$title}\" has been deleted.");
    }
}
