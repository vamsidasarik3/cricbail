@inject('seo', 'App\Services\SeoService')
@extends('layouts.app')

@section('title', $article->meta_title ?: $article->title)
@section('meta_description', $article->meta_description ?: $article->excerpt)
@section('canonical_url', $seo->canonicalUrl('/news/' . $article->slug))
@section('og_image', asset($article->featured_image ?: 'assets/images/cricket/cricbail_share_card.jpg'))
@section('og_type', 'article')
@section('article_published_time', $article->published_at->toIso8601String())
@section('article_modified_time', ($article->updated_at ?: $article->published_at)->toIso8601String())
@section('article_author', $article->author->name)
@section('article_section', $article->category->name)

@push('schema')
<script type="application/ld+json">
{!! json_encode($seo->articleJsonLd($article), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
<script type="application/ld+json">
{!! json_encode($seo->breadcrumbJsonLd([
    'Home' => route('home'),
    'Cricket News' => route('news.index'),
    $article->category->name => route('news.category', $article->category->slug),
    $article->title => route('news.show', $article->slug),
]), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush

@section('content')
<div class="container py-2">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-success text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('news.index') }}" class="text-success text-decoration-none">Cricket News</a></li>
            <li class="breadcrumb-item"><a href="{{ route('news.category', $article->category->slug) }}" class="text-success text-decoration-none">{{ $article->category->name }}</a></li>
            <li class="breadcrumb-item active text-truncate" style="max-width: 320px;" aria-current="page">{{ $article->title }}</li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Main Article Column -->
        <div class="col-lg-8">
            <article class="bg-white p-4 p-md-5 rounded-4 shadow-sm border border-light mb-5">
                <!-- Category and Date Meta -->
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <a href="{{ route('news.category', $article->category->slug) }}" class="category-badge">
                        {{ $article->category->name }}
                    </a>
                    <span class="text-muted small">&bull;</span>
                    <time class="text-muted small" datetime="{{ $article->published_at->toIso8601String() }}">
                        Published {{ $article->published_at->format('F j, Y &bull; g:i A') }}
                    </time>
                </div>

                <!-- Headline (H1) -->
                <h1 class="display-6 fw-bold text-dark mb-3">
                    {{ $article->title }}
                </h1>

                <!-- Excerpt / Sub-headline -->
                @if($article->excerpt)
                <p class="lead text-secondary border-start border-3 border-success ps-3 my-4">
                    {{ $article->excerpt }}
                </p>
                @endif

                <!-- Author Profile Row -->
                <div class="d-flex align-items-center justify-content-between py-3 my-4 border-top border-bottom">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-success bg-opacity-10 text-success fw-bold d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.2rem;">
                            🏏
                        </div>
                        <div>
                            <div class="fw-semibold text-dark">{{ $article->author->name }}</div>
                            <small class="text-muted">CricBail Senior Cricket Correspondent</small>
                        </div>
                    </div>

                    <!-- Quick Share Trigger -->
                    <div>
                        <button class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" id="btnShareArticle">
                            🔗 Share Story
                        </button>
                    </div>
                </div>

                <!-- Featured Image -->
                <figure class="my-4">
                    <img src="{{ asset($article->featured_image ?: 'assets/images/cricket/cricbail_share_card.jpg') }}" 
                         alt="{{ $article->title }}" 
                         class="img-fluid rounded-3 w-100 shadow-sm"
                         fetchpriority="high"
                         width="1200"
                         height="675"
                         style="max-height: 480px; object-fit: cover; aspect-ratio: 16/9;">
                    <figcaption class="figure-caption text-center mt-2 small text-muted">
                        CricBail Match Centre &bull; {{ $article->category->name }} Editorial Special
                    </figcaption>
                </figure>

                <!-- Article Full Content -->
                <div class="article-content mt-4">
                    {!! $article->content !!}
                </div>

                @if($article->source_name || $article->source_url)
                <!-- Verified Source Attribution -->
                <div class="p-3 my-4 bg-light rounded-3 border-start border-3 border-success small text-muted d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <span class="fw-semibold text-dark">Original Reporting / Source:</span>
                        @if($article->source_url)
                            <a href="{{ $article->source_url }}" target="_blank" rel="noopener noreferrer nofollow" class="text-success text-decoration-none ms-1 fw-medium">
                                {{ $article->source_name ?: $article->source_url }} &nearr;
                            </a>
                        @else
                            <span class="ms-1">{{ $article->source_name }}</span>
                        @endif
                    </div>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Verified Coverage</span>
                </div>
                @endif

                <hr class="my-5">

                <!-- Article Footer / Category Tags -->
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 small text-muted">
                    <div>
                        Filed under: 
                        <a href="{{ route('news.category', $article->category->slug) }}" class="text-success fw-semibold text-decoration-none">
                            {{ $article->category->name }}
                        </a>
                    </div>
                    <div>
                        Canonical Reference: <code>{{ App\Services\SeoService::canonicalUrl('news/' . $article->slug) }}</code>
                    </div>
                </div>
            </article>

            <!-- 5. COMMENTS SECTION -->
            <section class="comments-section bg-white p-4 p-md-5 rounded-4 shadow-sm border border-light mb-5" id="comments">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                    <h3 class="h4 fw-bold mb-0">💬 Reader Discussion ({{ $article->comments->count() }})</h3>
                    <small class="text-muted">Moderated for respectful sports debate</small>
                </div>

                <!-- Submission Alerts -->
                @if(session('comment_success'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    {{ session('comment_success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                <!-- Approved Comments List -->
                <div class="comments-list mb-5">
                    @forelse($article->comments as $comment)
                    <div class="p-3 mb-3 bg-light rounded-3 border-start border-3 border-success">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="text-dark">{{ $comment->visitor_name }}</strong>
                            <small class="text-muted">{{ $comment->created_at->diffForHumans() }}</small>
                        </div>
                        <p class="mb-0 text-secondary small">
                            {{ $comment->comment_body }}
                        </p>
                    </div>
                    @empty
                    <div class="text-center py-4 text-muted bg-light rounded-3">
                        <p class="mb-0">No reader comments yet. Be the first to share your thoughts on this story!</p>
                    </div>
                    @endforelse
                </div>

                <!-- Add Comment Form -->
                <div class="comment-form-container pt-3 border-top">
                    <h4 class="h5 fw-bold text-dark mb-3">Leave a Thoughtful Comment</h4>
                    
                    <form method="POST" action="{{ route('news.comment.store', $article->slug) }}" novalidate>
                        @csrf

                        <!-- Anti-spam Honeypot Field -->
                        <div class="d-none" aria-hidden="true">
                            <label for="website_hp">Leave this field blank</label>
                            <input type="text" name="website_hp" id="website_hp" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="visitor_name" class="form-label small fw-semibold text-secondary">Your Name *</label>
                                <input type="text" 
                                       class="form-control @error('visitor_name') is-invalid @enderror" 
                                       id="visitor_name" 
                                       name="visitor_name" 
                                       value="{{ old('visitor_name') }}" 
                                       required 
                                       placeholder="e.g. Rahul Sharma">
                                @error('visitor_name')
                                <div class="invalid-feedback small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label small fw-semibold text-secondary">Email Address * <span class="text-muted fw-normal">(Will not be published)</span></label>
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email') }}" 
                                       required 
                                       placeholder="name@example.com">
                                @error('email')
                                <div class="invalid-feedback small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="comment_body" class="form-label small fw-semibold text-secondary">Your Comment *</label>
                            <textarea class="form-control @error('comment_body') is-invalid @enderror" 
                                      id="comment_body" 
                                      name="comment_body" 
                                      rows="4" 
                                      required 
                                      placeholder="Share your cricketing perspective on this analysis or news... (min 3 characters)">{{ old('comment_body') }}</textarea>
                            @error('comment_body')
                            <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-success fw-semibold px-4">
                            Submit Comment for Review
                        </button>
                    </form>
                </div>
            </section>
        </div>

        <!-- Sidebar: Related Articles & Navigation -->
        <aside class="col-lg-4">
            <div class="position-sticky" style="top: 85px;">
                <!-- Related Articles Card -->
                <div class="card border-0 shadow-sm p-4 bg-white rounded-3 mb-4">
                    <h4 class="h5 fw-bold text-dark mb-3 border-bottom pb-2">
                        🏏 Related Stories
                    </h4>
                    
                    <div class="d-flex flex-column gap-3">
                        @forelse($relatedArticles as $related)
                        <div class="d-flex gap-3 align-items-start pb-2 border-bottom">
                            <a href="{{ route('news.show', $related->slug) }}" class="flex-shrink-0">
                                <img src="{{ asset($related->featured_image ?: 'assets/images/cricket/cricbail_share_card.jpg') }}" 
                                     alt="{{ $related->title }}" 
                                     class="rounded" 
                                     loading="lazy"
                                     decoding="async"
                                     width="75"
                                     height="60"
                                     style="width: 75px; height: 60px; object-fit: cover;">
                            </a>
                            <div>
                                <a href="{{ route('news.category', $related->category->slug) }}" class="category-badge mb-1" style="font-size: 0.65rem; padding: 0.15rem 0.4rem;">
                                    {{ $related->category->name }}
                                </a>
                                <h5 class="h6 fw-semibold mb-1" style="font-size: 0.85rem;">
                                    <a href="{{ route('news.show', $related->slug) }}" class="text-dark text-decoration-none">
                                        {{ Str::limit($related->title, 60) }}
                                    </a>
                                </h5>
                                <small class="text-muted" style="font-size: 0.75rem;">
                                    {{ $related->published_at->format('M d, Y') }}
                                </small>
                            </div>
                        </div>
                        @empty
                        <p class="text-muted small mb-0">No related articles found.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Quizzes Promo Card -->
                <div class="card border-0 shadow-sm p-4 text-white rounded-3" style="background: linear-gradient(135deg, #09261e 0%, #154536 100%);">
                    <span class="badge bg-warning text-dark fw-bold mb-2 align-self-start">🧠 Cricket Trivia</span>
                    <h5 class="fw-bold mb-2">Think you know Cricket?</h5>
                    <p class="small text-white-50 mb-3">
                        Take our World Cup and IPL quizzes to test your stats mastery.
                    </p>
                    <a href="{{ route('home') }}#quizzes-section" class="btn btn-sm btn-outline-warning fw-semibold">
                        Explore Quizzes &rarr;
                    </a>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var btn = document.getElementById('btnShareArticle');
        if (btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(window.location.href).then(function() {
                        alert('Article link copied to clipboard!');
                    });
                } else {
                    prompt('Copy this article link:', window.location.href);
                }
            });
        }
    });
</script>
@endpush
