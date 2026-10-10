@extends('layouts.app')

@section('title', '500 - Rain Delay - CricBail')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="container py-5 text-center my-auto">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-5 bg-white">
                <div class="display-1 fw-bold text-warning mb-2">500</div>
                <div class="fs-1 mb-3">🌧️ 🏏</div>
                <h1 class="h3 fw-bold text-dark mb-2">Temporary Rain Delay</h1>
                <p class="text-secondary mb-4">
                    Our pitch curators encountered an unexpected issue while processing your request. Please rest assured our technical groundskeepers are already on the field.
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="{{ route('home') }}" class="btn btn-success fw-bold px-4 py-2">
                        🏠 Return to Home
                    </a>
                    <a href="javascript:location.reload()" class="btn btn-outline-secondary fw-semibold px-4 py-2">
                        🔄 Refresh Page
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
