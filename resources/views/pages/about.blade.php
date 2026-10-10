@extends('layouts.app')

@section('title', App\Services\SeoService::title('About CricBail - Independent Cricket Journalism & Tactical Match Insights'))
@section('meta_description', 'Learn about CricBail editorial mission, cricket coverage principles, tactical analysis standards, and our commitment to clean, non-clickbait sports journalism.')
@section('canonical_url', App\Services\SeoService::canonicalUrl('about'))

@push('schema')
<script type="application/ld+json">
{!! json_encode(App\Services\SeoService::breadcrumbSchema([
    ['name' => 'Home', 'url' => App\Services\SeoService::canonicalUrl('/')],
    ['name' => 'About Us', 'url' => App\Services\SeoService::canonicalUrl('about')]
]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
@endpush

@section('content')
<div class="container py-3">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-success text-decoration-none">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">About Us</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="bg-white p-4 p-md-5 rounded-4 shadow-sm border border-light mb-5">
                <span class="badge bg-success bg-opacity-10 text-success fw-bold px-3 py-1.5 text-uppercase mb-2">Editorial Mission</span>
                <h1 class="display-6 fw-bold text-dark mb-4">About CricBail</h1>

                <p class="lead text-secondary mb-4">
                    <strong>CricBail</strong> was founded with a singular focus: to restore depth, thoughtful tactical analysis, and unhurried storytelling to digital cricket coverage.
                </p>

                <hr class="my-4">

                <h3 class="h4 fw-bold text-dark mb-3">🏏 What Sets Us Apart</h3>
                <p>
                    In an online ecosystem crowded with speculative rumors and fleeting social media commentary, CricBail focuses strictly on the craft of the sport:
                </p>
                <ul>
                    <li class="mb-2"><strong>Tactical Nuance:</strong> Dissecting bowling plans, field adjustments, and pitch conditions rather than superficial score recaps.</li>
                    <li class="mb-2"><strong>Domestic to Global:</strong> Giving domestic grassroots competitions like the Ranji Trophy equal dignity alongside marquee international tours and franchise tournaments.</li>
                    <li class="mb-2"><strong>Interactive Learning:</strong> Offering curated trivia quizzes designed to engage historical knowledge and stats appreciation without manipulative gamification.</li>
                    <li class="mb-2"><strong>Clean User Experience:</strong> A fast, server-rendered web application with zero clutter, no intrusive ad popups, and no bloated client-side single-page overhead.</li>
                </ul>

                <h3 class="h4 fw-bold text-dark mt-5 mb-3">📰 Editorial Ethics & Accuracy</h3>
                <p>
                    Our editorial team adheres to rigorous sports journalism principles. We do not invent rumors, alter quotes, or publish unverified dressing room speculation. When opinions and editorial commentaries are offered, they are clearly framed as analysis grounded in verifiable match events and historical data.
                </p>

                <div class="p-4 bg-light rounded-3 mt-4 border-start border-4 border-success">
                    <h5 class="fw-bold text-dark mb-1">Get in Touch</h5>
                    <p class="text-muted small mb-3">Have feedback, editorial tips, or analytical queries? We would love to hear from fellow cricket enthusiasts.</p>
                    <a href="{{ route('contact') }}" class="btn btn-sm btn-success fw-semibold">
                        Contact the Editorial Desk &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
