<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title', 'CricBail Admin Panel')</title>

    <!-- Bootstrap 5 CSS (Local) -->
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">

    <style>
        :root {
            --cb-admin-nav: #09261e;
            --cb-admin-sidebar: #0f382c;
            --cb-accent: #f59e0b;
            --cb-bg: #f1f5f9;
        }
        body {
            background-color: var(--cb-bg);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
        }
        .admin-navbar {
            background-color: var(--cb-admin-nav);
            border-bottom: 2px solid #144939;
        }
        .brand-accent {
            color: var(--cb-accent);
        }
        .sidebar {
            background-color: var(--cb-admin-sidebar);
            min-height: calc(100vh - 65px);
        }
        .sidebar .nav-link {
            color: #cbd5e1;
            font-weight: 500;
            padding: 0.75rem 1.25rem;
            border-radius: 0.5rem;
            margin-bottom: 0.25rem;
            transition: all 0.15s ease-in-out;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.12);
        }
        .sidebar .nav-link.active {
            border-left: 4px solid var(--cb-accent);
        }
        .admin-card {
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
            background: #ffffff;
        }
    </style>
    @stack('styles')
</head>
<body>
    <!-- Top Bar -->
    <nav class="navbar navbar-dark admin-navbar py-2 px-3 sticky-top shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ route('admin.dashboard') }}">
                <span>🏏 CRIC<span class="brand-accent">BAIL</span></span>
                <span class="badge bg-warning text-dark fs-7 px-2 py-1 rounded-pill">Admin</span>
            </a>

            <div class="d-flex align-items-center gap-3">
                <a href="{{ url('/') }}" target="_blank" class="btn btn-sm btn-outline-light d-none d-md-inline-block">
                    🌐 View Live Website
                </a>

                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary text-white dropdown-toggle border-0" type="button" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        👤 {{ auth()->user()->name }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userMenuButton">
                        <li><h6 class="dropdown-header">{{ auth()->user()->email }}</h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger d-flex align-items-center gap-2">
                                    🚪 Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Admin Container -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar py-3">
                <div class="position-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                                📊 Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.articles.*') ? 'active' : '' }}" href="{{ route('admin.articles.index') }}">
                                📰 Articles & News
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">
                                📁 Categories
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.quizzes.*') ? 'active' : '' }}" href="{{ route('admin.quizzes.index') }}">
                                🧠 Quizzes
                                @php $draftQuizzes = \App\Models\Quiz::where('status', 'draft')->count(); @endphp
                                @if($draftQuizzes > 0)
                                    <span class="badge bg-secondary ms-1 small">{{ $draftQuizzes }}</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.comments.*') ? 'active' : '' }}" href="{{ route('admin.comments.index') }}">
                                💬 Comments
                                @php $pendingCount = \App\Models\Comment::pending()->count(); @endphp
                                @if($pendingCount > 0)
                                    <span class="badge bg-warning text-dark ms-1 small">{{ $pendingCount }}</span>
                                @endif
                            </a>
                        </li>
                    </ul>

                    <hr class="border-secondary my-3">
                    <div class="px-3">
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </nav>

            <!-- Main Content Area -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                @if(session('status'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Scripts (Local) -->
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    @stack('scripts')
</body>
</html>
