<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories with associated article counts.
     */
    public function index(): View
    {
        $categories = Category::withCount('articles')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new category.
     */
    public function create(): View
    {
        return view('admin.categories.create');
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:categories,slug'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);
        $originalSlug = $slug;
        $counter = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-" . $counter++;
        }
        $validated['slug'] = $slug;

        $category = Category::create($validated);

        return redirect()->route('admin.categories.index')
            ->with('status', "Category \"{$category->name}\" created successfully.");
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(Category $category): View
    {
        $category->loadCount('articles');
        return view('admin.categories.edit', compact('category'));
    }

    /**
     * Update the specified category in storage.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name,' . $category->id],
            'slug' => ['required', 'string', 'max:100', 'unique:categories,slug,' . $category->id],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['slug'] = Str::slug($validated['slug']);

        $category->update($validated);

        return redirect()->route('admin.categories.index')
            ->with('status', "Category \"{$category->name}\" updated successfully.");
    }

    /**
     * Remove the specified category from storage while protecting existing article relationships.
     */
    public function destroy(Category $category): RedirectResponse
    {
        $articleCount = $category->articles()->count();

        // Enforce relational integrity
        if ($articleCount > 0) {
            return back()->with('error', "Cannot delete category \"{$category->name}\" because it is linked to {$articleCount} " . Str::plural('article', $articleCount) . ". Please reassign or delete the associated articles first.");
        }

        $name = $category->name;
        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('status', "Category \"{$name}\" deleted successfully.");
    }
}
