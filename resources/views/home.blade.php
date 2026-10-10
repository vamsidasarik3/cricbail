@extends('layouts.app')

@section('title', 'CricBail - Premier Cricket News, In-Depth Analysis & Interactive Quizzes')
@section('meta_description', 'Latest cricket news, IPL developments, international Test match analysis, domestic cricket talent, and interactive cricket trivia on CricBail.')

@section('content')
<div class="container">

    <!-- 1. FEATURED HERO ARTICLE -->
    @if($featuredArticle)
    <section class="featured-story-section mb-5">
        <div class="card news-card border-0 shadow-sm overflow-hidden">
            <div class="row g-0">
                <div class="col-lg-7">
                    <a href="{{ route('news.show', $featuredArticle->slug) }}">
                        <img src="{{ asset($featuredArticle->featured_image ?: 'assets/images/cricket/stadium_floodlights.svg') }}" 
                             alt="{{ $featuredArticle->title }}" 
                             class="card-thumbnail-featured w-100">
                    </a>
                </div>
                <div class="col-lg-5 d-flex flex-column justify-content-between p-4 p-lg-5 bg-white">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge bg-warning text-dark fw-bold px-2.5 py-1 text-uppercase">★ Top Story</span>
                            <a href="{{ route('news.category', $featuredArticle->category->slug) }}" class="category-badge">
                                {{ $featuredArticle->category->name }}
                            </a>
                        </div>

                        <h1 class="h2 fw-bold mb-3">
                            <a href="{{ route('news.show', $featuredArticle->slug) }}" class="text-dark text-decoration-none hover-primary">
                                {{ $featuredArticle->title }}
                            </a>
                        </h1>

                        <p class="text-muted lead fs-6 mb-4">
                            {{ $featuredArticle->excerpt }}
                        </p>
                    </div>

                    <div class="pt-3 border-top d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2 small text-muted">
                            <span class="fw-semibold text-dark">✍ {{ $featuredArticle->author->name }}</span>
                            <span>&bull;</span>
                            <time datetime="{{ $featuredArticle->published_at->toIso8601String() }}">
                                {{ $featuredArticle->published_at->format('M d, Y') }}
                            </time>
                        </div>
                        <a href="{{ route('news.show', $featuredArticle->slug) }}" class="btn btn-sm btn-outline-success fw-semibold px-3">
                            Read Full Story &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    <!-- 2. LATEST NEWS GRID -->
    <section class="latest-news-section mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
            <h2 class="h3 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <span>⚡ Latest Cricket News</span>
            </h2>
            <a href="{{ route('news.index') }}" class="text-success fw-semibold text-decoration-none small">
                View All News &rarr;
            </a>
        </div>

        <div class="row g-4">
            @forelse($latestArticles as $article)
            <div class="col-md-6 col-lg-3">
                <article class="news-card">
                    <a href="{{ route('news.show', $article->slug) }}">
                        <img src="{{ asset($article->featured_image ?: 'assets/images/cricket/stadium_floodlights.svg') }}" 
                             alt="{{ $article->title }}" 
                             class="card-thumbnail">
                    </a>
                    <div class="p-3 d-flex flex-column flex-grow-1 justify-content-between">
                        <div>
                            <div class="mb-2">
                                <a href="{{ route('news.category', $article->category->slug) }}" class="category-badge">
                                    {{ $article->category->name }}
                                </a>
                            </div>
                            <h3 class="h6 fw-bold mb-2">
                                <a href="{{ route('news.show', $article->slug) }}" class="text-dark text-decoration-none">
                                    {{ $article->title }}
                                </a>
                            </h3>
                            <p class="text-muted small mb-3">
                                {{ Str::limit($article->excerpt, 85) }}
                            </p>
                        </div>
                        <div class="pt-2 border-top d-flex justify-content-between align-items-center small text-muted">
                            <span>{{ $article->published_at->format('M d') }}</span>
                            <a href="{{ route('news.show', $article->slug) }}" class="text-success text-decoration-none fw-semibold">
                                Read &rarr;
                            </a>
                        </div>
                    </div>
                </article>
            </div>
            @empty
            <div class="col-12">
                <div class="alert alert-light border text-center p-4">
                    <p class="mb-0 text-muted">No additional news stories published yet.</p>
                </div>
            </div>
            @endforelse
        </div>
    </section>

    <!-- 3. CATEGORY-WISE SPOTLIGHT: IPL & INTERNATIONAL -->
    <section class="category-spotlights mb-5">
        <div class="row g-4">
            <!-- IPL Section -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm p-4 h-100 bg-white rounded-3">
                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                        <h3 class="h4 fw-bold text-dark mb-0">🏆 IPL 2026 Zone</h3>
                        @if($iplCategory)
                        <a href="{{ route('news.category', $iplCategory->slug) }}" class="text-success text-decoration-none small fw-semibold">
                            More IPL &rarr;
                        </a>
                        @endif
                    </div>

                    @forelse($iplArticles as $ipl)
                    <div class="d-flex gap-3 mb-3 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <a href="{{ route('news.show', $ipl->slug) }}" class="flex-shrink-0">
                            <img src="{{ asset($ipl->featured_image ?: 'assets/images/cricket/ipl_auction.svg') }}" 
                                 alt="{{ $ipl->title }}" 
                                 class="rounded" style="width: 100px; height: 75px; object-fit: cover;">
                        </a>
                        <div>
                            <h4 class="h6 fw-bold mb-1">
                                <a href="{{ route('news.show', $ipl->slug) }}" class="text-dark text-decoration-none">
                                    {{ $ipl->title }}
                                </a>
                            </h4>
                            <small class="text-muted">
                                {{ $ipl->published_at->format('M d, Y') }} &bull; {{ $ipl->author->name }}
                            </small>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted small">No IPL articles published currently.</p>
                    @endforelse
                </div>
            </div>

            <!-- International & Tactical Analysis -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm p-4 h-100 bg-white rounded-3">
                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                        <h3 class="h4 fw-bold text-dark mb-0">📊 Strategic & Test Analysis</h3>
                        @if($analysisCategory)
                        <a href="{{ route('news.category', $analysisCategory->slug) }}" class="text-success text-decoration-none small fw-semibold">
                            More Analysis &rarr;
                        </a>
                        @endif
                    </div>

                    @forelse($analysisArticles as $analysis)
                    <div class="d-flex gap-3 mb-3 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <a href="{{ route('news.show', $analysis->slug) }}" class="flex-shrink-0">
                            <img src="{{ asset($analysis->featured_image ?: 'assets/images/cricket/data_analysis.svg') }}" 
                                 alt="{{ $analysis->title }}" 
                                 class="rounded" style="width: 100px; height: 75px; object-fit: cover;">
                        </a>
                        <div>
                            <h4 class="h6 fw-bold mb-1">
                                <a href="{{ route('news.show', $analysis->slug) }}" class="text-dark text-decoration-none">
                                    {{ $analysis->title }}
                                </a>
                            </h4>
                            <small class="text-muted">
                                {{ $analysis->published_at->format('M d, Y') }} &bull; {{ $analysis->author->name }}
                            </small>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted small">No analysis articles published currently.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <!-- 4. RECENT CRICKET QUIZZES SECTION -->
    <section id="quizzes-section" class="recent-quizzes-section mb-5">
        <div class="p-4 p-lg-5 rounded-4 shadow-sm" style="background: linear-gradient(135deg, #09261e 0%, #164e3f 100%); color: #ffffff;">
            <div class="row align-items-center mb-4">
                <div class="col-md-8">
                    <span class="badge bg-warning text-dark fw-bold px-3 py-1.5 rounded-pill mb-2">🧠 Cricket IQ Zone</span>
                    <h2 class="h2 fw-bold text-white mb-2">Test Your Cricket Knowledge</h2>
                    <p class="text-white-50 mb-0">
                        Challenge yourself with curated trivia covering World Cup finals, historic spells, and IPL records.
                    </p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <span class="badge bg-white bg-opacity-10 text-white border border-white border-opacity-25 px-3 py-2">
                        {{ $recentQuizzes->count() }} Live Quizzes Available
                    </span>
                </div>
            </div>

            <div class="row g-3">
                @forelse($recentQuizzes as $quiz)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 bg-white text-dark p-3 rounded-3 shadow-sm border-0">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-success bg-opacity-10 text-success fw-bold">
                                {{ $quiz->questions_count }} Questions
                            </span>
                            <small class="text-muted">{{ $quiz->published_at->format('M Y') }}</small>
                        </div>
                        <h3 class="h6 fw-bold mb-2">{{ $quiz->title }}</h3>
                        <p class="text-muted small mb-3 flex-grow-1">
                            {{ Str::limit($quiz->description, 80) }}
                        </p>
                        <a href="{{ route('quizzes.show', $quiz->slug) }}" class="btn btn-sm btn-success w-100 fw-bold">
                            Play 120s Quiz &rarr;
                        </a>
                    </div>
                </div>
                @empty
                <div class="col-12 text-center text-white-50 py-3">
                    No quizzes published currently.
                </div>
                @endforelse
            </div>
        </div>
    </section>

</div>
@endsection
