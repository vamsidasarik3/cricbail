<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Title & SEO Meta Tags -->
    <title>@yield('title', 'CricBail - Cricket News, In-Depth Analysis & Quizzes')</title>
    <meta name="description" content="@yield('meta_description', 'Your premier destination for timely cricket news, tactical tournament analysis, franchise updates, and interactive cricket trivia.')">
    <meta name="robots" content="@yield('robots', 'index, follow')">
    <link rel="canonical" href="@yield('canonical_url', url()->current())">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="@yield('canonical_url', url()->current())">
    <meta property="og:title" content="@yield('title', 'CricBail - Cricket News, In-Depth Analysis & Quizzes')">
    <meta property="og:description" content="@yield('meta_description', 'Your premier destination for timely cricket news, tactical tournament analysis, and cricket trivia.')">
    <meta property="og:image" content="@yield('og_image', asset('assets/images/cricket/stadium_floodlights.svg'))">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'CricBail - Cricket News & Quizzes')">
    <meta name="twitter:description" content="@yield('meta_description', 'Timely cricket news, tactical tournament analysis, and interactive cricket trivia.')">
    <meta name="twitter:image" content="@yield('og_image', asset('assets/images/cricket/stadium_floodlights.svg'))">

    <!-- Bootstrap 5 CSS (Local) -->
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    
    <!-- Custom Theme Styling -->
    <style>
        :root {
            --cb-primary: #0a2920;
            --cb-primary-dark: #061a14;
            --cb-accent: #f59e0b;
            --cb-accent-hover: #d97706;
            --cb-bg: #f8fafc;
            --cb-surface: #ffffff;
            --cb-border: #e2e8f0;
            --cb-text: #1e293b;
            --cb-text-muted: #64748b;
        }
        body {
            background-color: var(--cb-bg);
            color: var(--cb-text);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.6;
        }
        /* Top ticker / header */
        .top-subbar {
            background-color: var(--cb-primary-dark);
            color: #94a3b8;
            font-size: 0.8rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        /* Main Navigation */
        .navbar-cricbail {
            background: linear-gradient(135deg, var(--cb-primary) 0%, #113f32 100%);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border-bottom: 2px solid var(--cb-accent);
        }
        .navbar-brand {
            font-weight: 800;
            letter-spacing: -0.5px;
            font-size: 1.5rem;
        }
        .brand-accent {
            color: var(--cb-accent);
        }
        .nav-link {
            font-weight: 500;
            font-size: 0.95rem;
            color: #e2e8f0 !important;
            padding: 0.5rem 0.85rem !important;
            transition: color 0.15s ease, background 0.15s ease;
            border-radius: 0.35rem;
        }
        .nav-link:hover, .nav-link.active {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .nav-link.active {
            color: var(--cb-accent) !important;
            font-weight: 600;
        }
        /* Cards & Media */
        .news-card {
            background: var(--cb-surface);
            border: 1px solid var(--cb-border);
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .news-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
        }
        .card-thumbnail {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background-color: #0c2d22;
        }
        .card-thumbnail-featured {
            width: 100%;
            height: 380px;
            object-fit: cover;
            background-color: #0c2d22;
        }
        @media (max-width: 768px) {
            .card-thumbnail-featured {
                height: 220px;
            }
            .card-thumbnail {
                height: 180px;
            }
        }
        .category-badge {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0.3rem 0.65rem;
            border-radius: 0.25rem;
            background-color: #e6f4ea;
            color: #137333;
            text-decoration: none;
            display: inline-block;
        }
        .category-badge:hover {
            background-color: #137333;
            color: #ffffff;
        }
        /* Footer */
        .footer-cricbail {
            background-color: var(--cb-primary-dark);
            color: #94a3b8;
            margin-top: auto;
            border-top: 3px solid var(--cb-accent);
        }
        .footer-link {
            color: #cbd5e1;
            text-decoration: none;
            transition: color 0.15s ease;
        }
        .footer-link:hover {
            color: var(--cb-accent);
        }
        /* Article formatting */
        .article-content {
            font-size: 1.15rem;
            line-height: 1.8;
            color: #24292f;
        }
        .article-content p {
            margin-bottom: 1.5rem;
        }
        .article-content h2, .article-content h3 {
            margin-top: 2rem;
            margin-bottom: 1rem;
            font-weight: 700;
            color: var(--cb-primary);
        }
    </style>

    @stack('styles')
</head>
<body>
    <!-- Top Utility Sub-bar -->
    <div class="top-subbar py-1.5 px-3 d-none d-md-block">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <span>📅 {{ now()->format('l, F j, Y') }}</span>
                <span class="mx-2">&bull;</span>
                <span class="text-white-50">Cricket News & Editorial Desk</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('sitemap') }}" class="text-decoration-none text-secondary small">Sitemap</a>
                <span class="text-secondary">&bull;</span>
                <a href="{{ route('admin.login') }}" class="text-decoration-none text-secondary small">Admin Portal</a>
            </div>
        </div>
    </div>

    <!-- Main Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-cricbail sticky-top py-2.5">
        <div class="container">
            <a class="navbar-brand text-white d-flex align-items-center gap-2" href="{{ route('home') }}">
                <span class="fs-3">🏏</span>
                <span>CRIC<span class="brand-accent">BAIL</span></span>
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#cricbailNav" aria-controls="cricbailNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="cricbailNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('category/ipl') ? 'active' : '' }}" href="{{ route('news.category', 'ipl') }}">IPL</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('category/international-cricket') ? 'active' : '' }}" href="{{ route('news.category', 'international-cricket') }}">International Cricket</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('category/domestic-cricket') ? 'active' : '' }}" href="{{ route('news.category', 'domestic-cricket') }}">Domestic Cricket</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('category/players') ? 'active' : '' }}" href="{{ route('news.category', 'players') }}">Players</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('category/analysis') ? 'active' : '' }}" href="{{ route('news.category', 'analysis') }}">Analysis</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('news.index') ? 'active' : '' }}" href="{{ route('news.index') }}">All News</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('quizzes.*') ? 'active' : '' }}" href="{{ route('quizzes.index') }}">Quizzes</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('quizzes.index') }}" class="btn btn-sm btn-outline-warning fw-semibold px-3 py-1.5">
                        🧠 Play Quiz
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="py-4">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="footer-cricbail py-5 mt-auto">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-lg-4 col-md-6">
                    <h5 class="text-white fw-bold d-flex align-items-center gap-2 mb-3">
                        <span>🏏 CRIC<span class="brand-accent">BAIL</span></span>
                    </h5>
                    <p class="small text-secondary mb-3">
                        CricBail provides accurate cricket journalism, tactical match recaps, domestic talent spotlights, and interactive cricket trivia for passionate fans worldwide.
                    </p>
                    <div class="small text-white-50">
                        Zero SPA bloat &bull; Server-rendered with Laravel Blade &bull; Powered by MySQL
                    </div>
                </div>

                <div class="col-lg-2 col-md-6">
                    <h6 class="text-white fw-bold mb-3">Coverage</h6>
                    <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                        <li><a href="{{ route('news.category', 'ipl') }}" class="footer-link">IPL 2026</a></li>
                        <li><a href="{{ route('news.category', 'international-cricket') }}" class="footer-link">International Cricket</a></li>
                        <li><a href="{{ route('news.category', 'domestic-cricket') }}" class="footer-link">Domestic Cricket</a></li>
                        <li><a href="{{ route('news.category', 'players') }}" class="footer-link">Player Profiles</a></li>
                        <li><a href="{{ route('news.category', 'analysis') }}" class="footer-link">Tactical Analysis</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h6 class="text-white fw-bold mb-3">Sections & Features</h6>
                    <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                        <li><a href="{{ route('news.index') }}" class="footer-link">All Published Articles</a></li>
                        <li><a href="{{ route('home') }}#quizzes-section" class="footer-link">Cricket Quizzes & Trivia</a></li>
                        <li><a href="{{ route('sitemap') }}" class="footer-link">XML Sitemap</a></li>
                        <li><a href="{{ route('admin.login') }}" class="footer-link">Editorial Desk (Admin)</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h6 class="text-white fw-bold mb-3">Information</h6>
                    <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                        <li><a href="{{ route('about') }}" class="footer-link">About CricBail</a></li>
                        <li><a href="{{ route('contact') }}" class="footer-link">Contact Editorial Team</a></li>
                        <li><a href="{{ route('privacy') }}" class="footer-link">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>

            <hr class="border-secondary my-4">

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small text-secondary">
                <div>
                    &copy; {{ date('Y') }} <strong>CricBail</strong>. All rights reserved. Professional Cricket Journalism.
                </div>
                <div>
                    <a href="{{ route('about') }}" class="text-secondary text-decoration-none me-3">About</a>
                    <a href="{{ route('contact') }}" class="text-secondary text-decoration-none me-3">Contact</a>
                    <a href="{{ route('privacy') }}" class="text-secondary text-decoration-none">Privacy Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- jQuery & Bootstrap 5 Bundle JS (Local) -->
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    @stack('scripts')
</body>
</html>
