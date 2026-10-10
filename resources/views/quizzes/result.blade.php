@extends('layouts.app')

@section('title', 'Quiz Result: ' . $quiz->title . ' - CricBail')
@section('robots', 'noindex, nofollow')

@push('styles')
<style>
    .score-circle {
        width: 140px;
        height: 140px;
        border-radius: 50%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        border: 8px solid;
    }
    .score-circle.perfect {
        border-color: #10b981;
        background-color: #ecfdf5;
        color: #065f46;
    }
    .score-circle.good {
        border-color: #f59e0b;
        background-color: #fffbeb;
        color: #92400e;
    }
    .score-circle.needs-work {
        border-color: #ef4444;
        background-color: #fef2f2;
        color: #991b1b;
    }
    .result-card-correct {
        border-left: 6px solid #10b981 !important;
        background-color: #f0fdf4 !important;
    }
    .result-card-incorrect {
        border-left: 6px solid #ef4444 !important;
        background-color: #fef2f2 !important;
    }
    .result-card-unanswered {
        border-left: 6px solid #f59e0b !important;
        background-color: #fffbeb !important;
    }
</style>
@endpush

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <!-- Breadcrumbs -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-muted">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('quizzes.index') }}" class="text-decoration-none text-muted">Quizzes</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('quizzes.show', $quiz->slug) }}" class="text-decoration-none text-muted">{{ Str::limit($quiz->title, 25) }}</a></li>
                    <li class="breadcrumb-item active text-dark" aria-current="page">Scorecard</li>
                </ol>
            </nav>

            @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
                ℹ️ {{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                ⚠️ {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <!-- Scorecard Summary Card -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5 text-center">
                <div class="card-header bg-dark text-white p-4" 
                     style="background: linear-gradient(135deg, #09261e 0%, #114b3b 100%);">
                    <span class="badge bg-warning text-dark fw-bold mb-2">Official Verified Scorecard</span>
                    <h1 class="h3 fw-bold text-white mb-1">{{ $quiz->title }}</h1>
                    <small class="text-white-50">Attempt ID: <code>{{ substr($attempt->attempt_token, 0, 8) }}...</code> &bull; Completed {{ $attempt->completed_at ? $attempt->completed_at->diffForHumans() : 'Just now' }}</small>
                </div>

                <div class="card-body p-4 p-md-5">
                    <!-- Circular Score Indicator -->
                    <div class="score-circle {{ $attempt->score >= 8 ? 'perfect' : ($attempt->score >= 5 ? 'good' : 'needs-work') }} mb-4">
                        <span class="display-5 fw-bold mb-0">{{ $attempt->score }}</span>
                        <span class="small fw-semibold text-uppercase">out of 10</span>
                    </div>

                    <!-- Performance Badge -->
                    <div class="mb-4">
                        @if($attempt->score === 10)
                            <h2 class="h4 fw-bold text-success mb-1">🏆 Perfect Ten! Cricket Legend!</h2>
                            <p class="text-secondary small mb-0">Flawless execution! You possess encyclopedic knowledge of the gentleman's game.</p>
                        @elseif($attempt->score >= 8)
                            <h2 class="h4 fw-bold text-success mb-1">🎖️ Superb Innings! Masterful Knowledge!</h2>
                            <p class="text-secondary small mb-0">Magnificent batting! You tackled the tough deliveries and put up a match-winning score.</p>
                        @elseif($attempt->score >= 5)
                            <h2 class="h4 fw-bold text-warning mb-1">🏏 Solid Knock! Good Cricket Brain!</h2>
                            <p class="text-secondary small mb-0">Respectable fight at the crease! A few more practice sessions and you'll hit a century.</p>
                        @else
                            <h2 class="h4 fw-bold text-danger mb-1">🎯 Tough Match! Back to Net Practice!</h2>
                            <p class="text-secondary small mb-0">Tricky bowling conditions today. Review the explanations below and try another shot!</p>
                        @endif
                    </div>

                    <!-- Key Stats Grid -->
                    <div class="row g-3 justify-content-center mb-4">
                        <div class="col-6 col-sm-4">
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="small text-muted mb-1">Accuracy</div>
                                <div class="h4 fw-bold text-dark mb-0">{{ $attempt->percentage }}%</div>
                            </div>
                        </div>

                        <div class="col-6 col-sm-4">
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="small text-muted mb-1">Time Taken</div>
                                <div class="h4 fw-bold text-dark mb-0">{{ $attempt->formatted_time_taken }}</div>
                            </div>
                        </div>

                        <div class="col-6 col-sm-4">
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="small text-muted mb-1">Time Limit</div>
                                <div class="h4 fw-bold text-dark mb-0">120s</div>
                            </div>
                        </div>
                    </div>

                    <!-- Action CTAs -->
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <a href="{{ route('quizzes.play', $quiz->slug) }}" class="btn btn-success px-4 py-2 fw-bold rounded-pill shadow-sm">
                            🔄 Play Again (New Attempt)
                        </a>

                        <a href="{{ route('quizzes.index') }}" class="btn btn-outline-dark px-4 py-2 fw-semibold rounded-pill">
                            Explore More Quizzes &rarr;
                        </a>
                    </div>
                </div>
            </div>

            <!-- Detailed Question Breakdown -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h4 fw-bold text-dark mb-0">Detailed Question Breakdown & Explanations</h2>
                    <span class="badge bg-light text-secondary border">10 Questions Reviewed</span>
                </div>

                <div class="d-flex flex-column gap-4">
                    @foreach($questions as $index => $q)
                        @php
                            $userChoice = $userAnswers[$q->id] ?? null;
                            $isCorrect = $q->isOptionCorrect($userChoice);
                            $isUnanswered = empty($userChoice);
                            $cardClass = $isCorrect ? 'result-card-correct' : ($isUnanswered ? 'result-card-unanswered' : 'result-card-incorrect');
                        @endphp

                        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-4.5 bg-white {{ $cardClass }}">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-dark px-3 py-1.5 rounded-pill small fw-bold">
                                    Question #{{ $index + 1 }}
                                </span>

                                <div>
                                    @if($isCorrect)
                                        <span class="badge bg-success px-3 py-1.5 rounded-pill fw-bold">
                                            ✓ Correct (+1 pt)
                                        </span>
                                    @elseif($isUnanswered)
                                        <span class="badge bg-warning text-dark px-3 py-1.5 rounded-pill fw-bold">
                                            ⚠️ Unanswered (0 pts)
                                        </span>
                                    @else
                                        <span class="badge bg-danger px-3 py-1.5 rounded-pill fw-bold">
                                            ✗ Incorrect (0 pts)
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Question Text -->
                            <h3 class="h5 fw-bold text-dark mb-3">
                                {{ $q->question_text }}
                            </h3>

                            <!-- User's Answer vs Correct Answer -->
                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <div class="p-2.5 rounded-3 border {{ $isCorrect ? 'bg-success bg-opacity-10 border-success' : ($isUnanswered ? 'bg-warning bg-opacity-10 border-warning' : 'bg-danger bg-opacity-10 border-danger') }} small">
                                        <div class="fw-semibold text-muted mb-1">Your Selection:</div>
                                        <div class="fw-bold {{ $isCorrect ? 'text-success' : ($isUnanswered ? 'text-dark' : 'text-danger') }}">
                                            @if($isUnanswered)
                                                <em>None (Skipped)</em>
                                            @else
                                                Option {{ strtoupper($userChoice) }}: {{ $q->options[$userChoice] ?? 'Unknown' }}
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="p-2.5 rounded-3 border bg-success bg-opacity-10 border-success small">
                                        <div class="fw-semibold text-muted mb-1">Official Correct Answer:</div>
                                        <div class="fw-bold text-success">
                                            Option {{ strtoupper($q->correct_option) }}: {{ $q->correct_option_text }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Explanation -->
                            @if(!empty($q->explanation))
                            <div class="p-3 bg-white bg-opacity-75 rounded-3 border border-secondary border-opacity-25 small text-secondary">
                                <strong class="text-dark d-block mb-1">💡 Cricket Trivia Fact & Explanation:</strong>
                                {{ $q->explanation }}
                            </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Bottom Return Bar -->
            <div class="text-center py-4 border-top">
                <a href="{{ route('quizzes.index') }}" class="btn btn-outline-success px-4 fw-semibold rounded-pill">
                    &larr; Return to Quizzes Hub
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
