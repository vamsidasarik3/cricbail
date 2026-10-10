@extends('layouts.app')

@section('title', '403 - Restricted Pavilion - CricBail')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="container py-5 text-center my-auto">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-5 bg-white">
                <div class="display-1 fw-bold text-danger mb-2">403</div>
                <div class="fs-1 mb-3">🚫 🔒</div>
                <h1 class="h3 fw-bold text-dark mb-2">Restricted Pavilion Area</h1>
                <p class="text-secondary mb-4">
                    {{ $exception->getMessage() ?: 'You do not have administrative clearance to access this dressing room or editorial resource.' }}
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="{{ route('home') }}" class="btn btn-success fw-bold px-4 py-2">
                        🏠 Back to Home
                    </a>
                    <a href="{{ route('admin.login') }}" class="btn btn-outline-dark fw-semibold px-4 py-2">
                        👤 Admin Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
