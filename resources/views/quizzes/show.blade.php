@extends('layouts.app')

@section('title', App\Services\SeoService::title($quiz->title . ' - Cricket Quiz'))
@section('meta_description', Str::limit(strip_tags($quiz->description), 160))
@section('canonical_url', App\Services\SeoService::canonicalUrl('quizzes/' . $quiz->slug))

@push('schema')
<script type="application/ld+json">
{!! json_encode(App\Services\SeoService::quizSchema($quiz), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
<script type="application/ld+json">
{!! json_encode(App\Services\SeoService::breadcrumbSchema([
    ['name' => 'Home', 'url' => App\Services\SeoService::canonicalUrl('/')],
    ['name' => 'Cricket Quizzes', 'url' => App\Services\SeoService::canonicalUrl('quizzes')],
    ['name' => $quiz->title, 'url' => App\Services\SeoService::canonicalUrl('quizzes/' . $quiz->slug)]
]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
@endpush

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Breadcrumbs -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-muted">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('quizzes.index') }}" class="text-decoration-none text-muted">Quizzes</a></li>
                    <li class="breadcrumb-item active text-dark" aria-current="page">{{ Str::limit($quiz->title, 40) }}</li>
                </ol>
            </nav>

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                ⚠️ {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                ⏰ {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <!-- Quiz Briefing Card -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-dark text-white p-4 p-md-5" 
                     style="background: linear-gradient(135deg, #09261e 0%, #114b3b 100%); border-bottom: 4px solid #f59e0b;">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-warning text-dark fw-bold">Official Match Trivia</span>
                        <span class="text-white-50 small">&bull; {{ $quiz->questions_count }} Questions</span>
                    </div>
                    <h1 class="h2 fw-bold text-white mb-3">{{ $quiz->title }}</h1>
                    <p class="lead text-white-50 mb-0">
                        {{ $quiz->description }}
                    </p>
                </div>

                <div class="card-body p-4 p-md-5">
                    <h3 class="h5 fw-bold text-dark mb-3">🏏 Official Gameplay Rules & Guidelines</h3>

                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3 border-start border-4 border-success h-100">
                                <div class="fw-bold text-dark mb-1">⏱️ Total Time Limit</div>
                                <div class="small text-secondary">
                                    Exactly <strong>120 seconds (2 minutes)</strong>. The clock starts the moment you launch the quiz.
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3 border-start border-4 border-success h-100">
                                <div class="fw-bold text-dark mb-1">🎯 Questions & Scoring</div>
                                <div class="small text-secondary">
                                    Exactly <strong>10 questions</strong>. 1 point per correct answer. Maximum score: <strong>10</strong>.
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3 border-start border-4 border-success h-100">
                                <div class="fw-bold text-dark mb-1">🚫 No Negative Marking</div>
                                <div class="small text-secondary">
                                    No penalties for incorrect attempts. Make an educated guess if you aren't sure!
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3 border-start border-4 border-success h-100">
                                <div class="fw-bold text-dark mb-1">🔀 Smooth Navigation</div>
                                <div class="small text-secondary">
                                    Navigate freely with Previous/Next buttons or the question palette. Change choices anytime before submission.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-light border border-warning bg-warning bg-opacity-10 text-dark small mb-4">
                        <strong>📌 Important Security & Fair Play Policy:</strong>
                        <ul class="mb-0 ps-3 mt-1">
                            <li>Refreshing the page will <strong>not</strong> reset your timer; remaining time is tracked securely on the server.</li>
                            <li>The quiz will <strong>automatically submit</strong> your selected answers when the 120-second timer reaches zero.</li>
                            <li>You can manually click <strong>Submit Quiz</strong> whenever you finish.</li>
                            <li>Registration is <strong>not required</strong>. Results and comprehensive explanations will be displayed immediately upon submission.</li>
                        </ul>
                    </div>

                    <!-- CTA Actions -->
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 pt-3 border-top">
                        <a href="{{ route('quizzes.index') }}" class="btn btn-outline-secondary px-4">
                            &larr; Back to All Quizzes
                        </a>

                        <a href="{{ route('quizzes.play', $quiz->slug) }}" class="btn btn-success btn-lg px-5 fw-bold shadow-sm rounded-pill text-uppercase">
                            🚀 Start Quiz Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
