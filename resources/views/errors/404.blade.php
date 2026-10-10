@extends('layouts.app')

@section('title', '404 - Over Bowled, Page Not Found - CricBail')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="container py-5 text-center my-auto">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-5 bg-white">
                <div class="display-1 fw-bold text-success mb-2">404</div>
                <div class="fs-1 mb-3">🏏 💨</div>
                <h1 class="h3 fw-bold text-dark mb-2">Howzat?! That Page Is Out!</h1>
                <p class="text-secondary mb-4">
                    The cricket story, category, or quiz you are looking for might have been retired, moved, or the URL might be misbowled.
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="{{ route('home') }}" class="btn btn-success fw-bold px-4 py-2">
                        🏠 Return to Home
                    </a>
                    <a href="{{ route('news.index') }}" class="btn btn-outline-secondary fw-semibold px-4 py-2">
                        📰 Latest Cricket News
                    </a>
                    <a href="{{ route('quizzes.index') }}" class="btn btn-outline-primary fw-semibold px-4 py-2">
                        🧠 Play Cricket Quizzes
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
