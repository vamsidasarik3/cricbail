@extends('admin.layouts.admin')

@section('title', 'Manage News Articles - CricBail Admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Cricket News & Articles</h1>
        <p class="text-secondary mb-0 small">Publish, edit, curate featured stories, and manage article categories.</p>
    </div>

    <a href="{{ route('admin.articles.create') }}" class="btn btn-success fw-bold d-flex align-items-center gap-2">
        <span>✍️</span> Write New Article
    </a>
</div>

<!-- Filters & Search Toolbar -->
<div class="card admin-card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
    <form method="GET" action="{{ route('admin.articles.index') }}" class="row g-2 align-items-center">
        <!-- Search Keyword -->
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0">🔍</span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Search by title, excerpt, or content..." value="{{ $search }}">
            </div>
        </div>

        <!-- Category Filter -->
        <div class="col-md-3">
            <select name="category_id" class="form-select">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ (string)$categoryId === (string)$cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
                @endforeach
            </select>
        </div>

        <!-- Status Filter -->
        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="">All Statuses</option>
                <option value="published" {{ $status === 'published' ? 'selected' : '' }}>Published ({{ $counts['published'] }})</option>
                <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Draft ({{ $counts['draft'] }})</option>
            </select>
        </div>

        <!-- Filter Submit & Reset -->
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary w-100 fw-semibold">
                Filter
            </button>
            @if($search || $categoryId || $status)
            <a href="{{ route('admin.articles.index') }}" class="btn btn-outline-secondary" title="Reset Filters">
                ✕
            </a>
            @endif
        </div>
    </form>
</div>

<!-- Articles Table Card -->
<div class="card admin-card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark">Articles List ({{ $articles->total() }})</h2>
        <div class="d-flex gap-2 small">
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                ● Published: {{ $counts['published'] }}
            </span>
            <span class="badge bg-secondary bg-opacity-10 text-secondary border">
                ○ Drafts: {{ $counts['draft'] }}
            </span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-secondary small text-uppercase">
                <tr>
                    <th style="width: 40%;">Article & Author</th>
                    <th style="width: 15%;">Category</th>
                    <th style="width: 12%;">Comments</th>
                    <th style="width: 12%;">Status</th>
                    <th style="width: 21%;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($articles as $article)
                <tr>
                    <!-- Article info + thumbnail -->
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <img src="{{ asset($article->featured_image ?: 'assets/images/cricket/stadium_floodlights.svg') }}" 
                                 alt="{{ $article->title }}" 
                                 class="rounded-2 object-fit-cover border" 
                                 style="width: 58px; height: 42px;">
                            <div class="text-truncate" style="max-width: 380px;">
                                <div class="fw-bold text-dark text-truncate" title="{{ $article->title }}">
                                    {{ $article->title }}
                                </div>
                                <div class="small text-muted font-monospace text-truncate">
                                    /{{ $article->slug }}
                                </div>
                                <div class="small text-muted">
                                    By {{ $article->author ? $article->author->name : 'Staff' }} &bull; 
                                    {{ $article->created_at->format('M d, Y') }}
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Category -->
                    <td>
                        @if($article->category)
                            <a href="{{ route('admin.articles.index', ['category_id' => $article->category_id]) }}" class="badge bg-light text-dark border text-decoration-none">
                                {{ $article->category->name }}
                            </a>
                        @else
                            <span class="badge bg-light text-muted border">None</span>
                        @endif
                    </td>

                    <!-- Comments count -->
                    <td>
                        <a href="{{ route('admin.comments.index', ['article_id' => $article->id]) }}" class="badge bg-light text-secondary border text-decoration-none">
                            💬 {{ $article->comments_count }}
                        </a>
                    </td>

                    <!-- Status -->
                    <td>
                        @if($article->status === 'published')
                            <span class="badge bg-success px-2.5 py-1">Published</span>
                        @else
                            <span class="badge bg-secondary px-2.5 py-1">Draft</span>
                        @endif
                    </td>

                    <!-- Actions -->
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-1 flex-wrap">
                            <!-- Edit -->
                            <a href="{{ route('admin.articles.edit', $article->id) }}" class="btn btn-sm btn-outline-secondary" title="Edit Article">
                                ✏️ Edit
                            </a>

                            <!-- Preview / Public Link -->
                            @if($article->status === 'published')
                                <a href="{{ route('news.show', $article->slug) }}" target="_blank" class="btn btn-sm btn-outline-info" title="View Public Article">
                                    ↗ View
                                </a>
                            @endif

                            <!-- Publish / Unpublish Toggle -->
                            @if($article->status === 'draft')
                                <form method="POST" action="{{ route('admin.articles.publish', $article->id) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Publish Article">
                                        🚀 Publish
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.articles.unpublish', $article->id) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-warning text-dark" title="Unpublish Article">
                                        ⏸️ Unpublish
                                    </button>
                                </form>
                            @endif

                            <!-- Delete -->
                            <form method="POST" action="{{ route('admin.articles.destroy', $article->id) }}" class="d-inline" onsubmit="return confirm('Delete this article permanently? This will remove its comments as well.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Article">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                        No articles found matching your criteria.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($articles->hasPages())
    <div class="card-footer bg-white py-3 border-top d-flex justify-content-center">
        {{ $articles->links() }}
    </div>
    @endif
</div>
@endsection
