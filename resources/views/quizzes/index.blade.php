@extends('layouts.app')

@section('title', 'Cricket Quizzes & Trivia Challenges - CricBail')
@section('meta_description', 'Put your cricket knowledge to the ultimate test with CricBail\'s 10-question, 2-minute timed quizzes covering World Cups, IPL, Test cricket legends, and stats.')

@section('content')
<div class="container py-4">
    <!-- Header Hero Banner -->
    <div class="bg-dark text-white rounded-4 p-4 p-md-5 mb-5 shadow-sm position-relative overflow-hidden" 
         style="background: linear-gradient(135deg, #09261e 0%, #114b3b 100%); border-left: 6px solid #f59e0b;">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="badge bg-warning text-dark fw-bold px-3 py-1.5 rounded-pill mb-3">
                    ⏱️ 120-Second Timed Challenge
                </span>
                <h1 class="display-5 fw-bold text-white mb-3">Cricket Trivia & Masterclass Quizzes</h1>
                <p class="lead text-white-50 mb-0">
                    Step up to the crease! 10 questions, 2 minutes on the clock, 1 point per question, and no negative marking. 
                    Can you hit a perfect ten before the timer strikes zero?
                </p>
            </div>
            <div class="col-lg-4 text-center d-none d-lg-block">
                <div class="display-1">🧠🏏</div>
            </div>
        </div>
    </div>

    <!-- Quizzes Grid -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h4 fw-bold text-dark mb-0">Featured Published Quizzes</h2>
            <small class="text-muted">Select a quiz to review the rules and begin your 2-minute innings</small>
        </div>
        <span class="badge bg-light text-secondary border px-3 py-2 fs-6">
            {{ $quizzes->total() }} Available {{ Str::plural('Quiz', $quizzes->total()) }}
        </span>
    </div>

    @if($quizzes->isEmpty())
        <div class="text-center py-5 bg-white rounded-4 shadow-sm border p-4 my-4">
            <div class="display-4 text-muted mb-3">🏏</div>
            <h3 class="h5 fw-bold text-dark">No Quizzes Currently Live</h3>
            <p class="text-secondary mb-3">Our cricket curators are drafting fresh 10-question challenges. Check back shortly!</p>
            <a href="{{ route('news.index') }}" class="btn btn-outline-success">Browse Cricket News</a>
        </div>
    @else
        <div class="row g-4 mb-5">
            @foreach($quizzes as $quiz)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden position-relative news-card">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1">
                                🎯 10 Questions
                            </span>
                            <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50 px-2.5 py-1">
                                ⏱️ 120 Seconds
                            </span>
                        </div>

                        <h3 class="h5 fw-bold text-dark mb-2">
                            <a href="{{ route('quizzes.show', $quiz->slug) }}" class="text-dark text-decoration-none hover-primary">
                                {{ $quiz->title }}
                            </a>
                        </h3>

                        <p class="text-secondary small flex-grow-1 mb-4">
                            {{ Str::limit($quiz->description, 130) }}
                        </p>

                        <div class="border-top pt-3 d-flex justify-content-between align-items-center">
                            <div class="small text-muted">
                                🏆 Max Score: <strong>10</strong>
                            </div>
                            <a href="{{ route('quizzes.show', $quiz->slug) }}" class="btn btn-sm btn-success px-3 fw-bold rounded-pill">
                                Play Quiz &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="d-flex justify-content-center">
            {{ $quizzes->links() }}
        </div>
    @endif
</div>
@endsection
