@extends('layouts.app')

@section('title', 'Privacy Policy - CricBail')
@section('meta_description', 'Review the CricBail Privacy Policy regarding data protection, reader comments, analytics, and cookie transparency.')

@section('content')
<div class="container py-3">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-success text-decoration-none">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Privacy Policy</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="bg-white p-4 p-md-5 rounded-4 shadow-sm border border-light mb-5">
                <span class="badge bg-secondary bg-opacity-10 text-secondary fw-bold px-3 py-1.5 text-uppercase mb-2">Legal & Privacy</span>
                <h1 class="display-6 fw-bold text-dark mb-4">Privacy Policy</h1>
                <p class="text-muted small mb-4">Last Updated: October 2026</p>

                <p>
                    At <strong>CricBail</strong>, accessible at <code>http://cricbail.vkrshna.in:8084</code>, we consider the privacy of our visitors of supreme importance. This Privacy Policy document outlines the types of information collected and how it is used.
                </p>

                <h3 class="h5 fw-bold text-dark mt-4 mb-2">1. Reader Comments</h3>
                <p class="small text-secondary">
                    When visitors submit comments on articles, we collect the submitted name, email address, and comment text. Your email address is strictly used for moderation and verification purposes and is never made publicly visible or sold to third parties.
                </p>

                <h3 class="h5 fw-bold text-dark mt-4 mb-2">2. Log Files & Server Metrics</h3>
                <p class="small text-secondary">
                    Like most standard web servers, our web host collects standard log files including internet protocol (IP) addresses, browser type, internet service provider (ISP), date/time stamps, referring/exit pages, and number of clicks. These are not linked to any personally identifiable information.
                </p>

                <h3 class="h5 fw-bold text-dark mt-4 mb-2">3. Cookies & Session Storage</h3>
                <p class="small text-secondary">
                    CricBail uses standard session cookies solely for authentication and security tokens (CSRF protection) when administrative users log in or visitors interact with forms. We do not use intrusive third-party tracking or behavioral advertising beacons.
                </p>

                <h3 class="h5 fw-bold text-dark mt-4 mb-2">4. Data Safety & Security</h3>
                <p class="small text-secondary">
                    We implement standard security measures, including bcrypt password hashing, prepared database queries, and CSRF protection to safeguard your information against unauthorized access.
                </p>

                <h3 class="h5 fw-bold text-dark mt-4 mb-2">5. Contact Information</h3>
                <p class="small text-secondary">
                    For any questions regarding our privacy practices, please contact us via our <a href="{{ route('contact') }}" class="text-success text-decoration-none">Contact Desk</a>.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
