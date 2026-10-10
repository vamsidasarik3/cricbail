@extends('layouts.app')

@section('title', App\Services\SeoService::title(($selectedCategory ?? null) ? $selectedCategory->name . ' Cricket News' : 'Cricket News & Tournament Updates'))
@section('meta_description', 'Browse the latest cricket news, tactical editorial breakdowns, match reports, and player profiles across all formats on CricBail.')
@section('canonical_url', App\Services\SeoService::canonicalUrl(request()->path(), ['page']))

@push('schema')
<script type="application/ld+json">
{!! json_encode(App\Services\SeoService::breadcrumbSchema([
    ['name' => 'Home', 'url' => App\Services\SeoService::canonicalUrl('/')],
    ['name' => 'Cricket News', 'url' => App\Services\SeoService::canonicalUrl('news')]
]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
@endpush

@section('content')
<div class="container py-2">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-success text-decoration-none">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Cricket News</li>
            @if($selectedCategory)
                <li class="breadcrumb-item active" aria-current="page">{{ $selectedCategory->name }}</li>
            @endif
        </ol>
    </nav>

    <!-- Header & Category Filters -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 border-bottom pb-3">
        <div>
            <h1 class="h2 fw-bold text-dark mb-1">
                @if($selectedCategory)
                    {{ $selectedCategory->name }} News
                @else
                    All Cricket News
                @endif
            </h1>
            <p class="text-muted small mb-0">
                Showing {{ $articles->total() }} published editorial stories and match reports
            </p>
        </div>

        <!-- Filter Pills -->
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('news.index') }}" 
               class="btn btn-sm {{ !$selectedCategory ? 'btn-success fw-semibold' : 'btn-outline-secondary' }}">
                All Stories
            </a>
            @foreach($categories as $cat)
            <a href="{{ route('news.category', $cat->slug) }}" 
               class="btn btn-sm {{ $selectedCategory && $selectedCategory->id === $cat->id ? 'btn-success fw-semibold' : 'btn-outline-secondary' }}">
                {{ $cat->name }} <span class="badge bg-light text-dark ms-1">{{ $cat->articles_count }}</span>
            </a>
            @endforeach
        </div>
    </div>

    <!-- Articles Grid -->
    <div class="row g-4 mb-5">
        @forelse($articles as $article)
        <div class="col-md-6 col-lg-4">
            <article class="news-card">
                <a href="{{ route('news.show', $article->slug) }}">
                    <img src="{{ asset($article->featured_image ?: 'assets/images/cricket/cricbail_share_card.jpg') }}" 
                         alt="{{ $article->title }}" 
                         width="400"
                         height="225"
                         loading="lazy"
                         class="card-thumbnail">
                </a>
                <div class="p-3.5 p-4 d-flex flex-column flex-grow-1 justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <a href="{{ route('news.category', $article->category->slug) }}" class="category-badge">
                                {{ $article->category->name }}
                            </a>
                            <small class="text-muted">
                                {{ $article->published_at->format('M d, Y') }}
                            </small>
                        </div>

                        <h2 class="h5 fw-bold mb-2">
                            <a href="{{ route('news.show', $article->slug) }}" class="text-dark text-decoration-none">
                                {{ $article->title }}
                            </a>
                        </h2>

                        <p class="text-muted small mb-3">
                            {{ Str::limit($article->excerpt, 110) }}
                        </p>
                    </div>

                    <div class="pt-3 border-top d-flex justify-content-between align-items-center small">
                        <span class="text-muted">By <strong>{{ $article->author->name }}</strong></span>
                        <a href="{{ route('news.show', $article->slug) }}" class="text-success fw-semibold text-decoration-none">
                            Read Article &rarr;
                        </a>
                    </div>
                </div>
            </article>
        </div>
        @empty
        <!-- Empty State -->
        <div class="col-12">
            <div class="card border-0 shadow-sm p-5 text-center bg-white rounded-3">
                <div class="display-3 text-muted mb-3">🏏</div>
                <h3 class="h4 fw-bold">No Published Articles Found</h3>
                <p class="text-muted mb-4">
                    There are currently no published articles in this category.
                </p>
                <div>
                    <a href="{{ route('news.index') }}" class="btn btn-outline-success">
                        Clear Filters & View All News
                    </a>
                </div>
            </div>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($articles->hasPages())
    <div class="d-flex justify-content-center mb-5">
        {{ $articles->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>
@endsection
