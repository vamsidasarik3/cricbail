@extends('layouts.app')

@section('title', $category->name . ' - Cricket News, Reports & Highlights | CricBail')
@section('meta_description', $category->description ?: 'Browse the latest cricket coverage and articles in ' . $category->name . ' on CricBail.')

@section('content')
<div class="container py-2">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-success text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('news.index') }}" class="text-success text-decoration-none">Cricket News</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $category->name }}</li>
        </ol>
    </nav>

    <!-- Category Banner Header -->
    <div class="p-4 p-md-5 rounded-4 mb-4" style="background: linear-gradient(135deg, #0f382c 0%, #1c5e47 100%); color: #ffffff;">
        <span class="badge bg-warning text-dark fw-bold px-3 py-1 text-uppercase mb-2">Category Desk</span>
        <h1 class="display-6 fw-bold mb-2">{{ $category->name }}</h1>
        @if($category->description)
            <p class="lead fs-6 text-white-50 mb-0 max-w-700">
                {{ $category->description }}
            </p>
        @endif
    </div>

    <!-- Category Articles Grid -->
    <div class="row g-4 mb-5">
        @forelse($articles as $article)
        <div class="col-md-6 col-lg-4">
            <article class="news-card">
                <a href="{{ route('news.show', $article->slug) }}">
                    <img src="{{ asset($article->featured_image ?: 'assets/images/cricket/stadium_floodlights.svg') }}" 
                         alt="{{ $article->title }}" 
                         class="card-thumbnail">
                </a>
                <div class="p-4 d-flex flex-column flex-grow-1 justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="category-badge">{{ $category->name }}</span>
                            <small class="text-muted">{{ $article->published_at->format('M d, Y') }}</small>
                        </div>

                        <h2 class="h5 fw-bold mb-2">
                            <a href="{{ route('news.show', $article->slug) }}" class="text-dark text-decoration-none">
                                {{ $article->title }}
                            </a>
                        </h2>

                        <p class="text-muted small mb-3">
                            {{ Str::limit($article->excerpt, 100) }}
                        </p>
                    </div>

                    <div class="pt-3 border-top d-flex justify-content-between align-items-center small">
                        <span class="text-muted">By <strong>{{ $article->author->name }}</strong></span>
                        <a href="{{ route('news.show', $article->slug) }}" class="text-success fw-semibold text-decoration-none">
                            Read More &rarr;
                        </a>
                    </div>
                </div>
            </article>
        </div>
        @empty
        <!-- Empty State -->
        <div class="col-12">
            <div class="card border-0 shadow-sm p-5 text-center bg-white rounded-3">
                <div class="display-4 text-muted mb-2">🏏</div>
                <h3 class="h5 fw-bold">No Published Stories in {{ $category->name }} Yet</h3>
                <p class="text-muted small mb-4">
                    Our editorial team is preparing fresh coverage for this section.
                </p>
                <div>
                    <a href="{{ route('news.index') }}" class="btn btn-outline-success btn-sm">
                        Browse All Cricket News
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
