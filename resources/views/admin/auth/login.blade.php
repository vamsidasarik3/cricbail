<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>Administrator Login - CricBail</title>

    <!-- Bootstrap 5 CSS (Local) -->
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">

    <style>
        body {
            background: linear-gradient(135deg, #09261e 0%, #154536 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            padding: 1.5rem;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }
        .login-header {
            background-color: #0c3327;
            padding: 2.25rem 2rem 1.75rem;
            text-align: center;
            color: #ffffff;
        }
        .brand-accent {
            color: #f59e0b;
        }
        .btn-cricbail {
            background-color: #0f382c;
            color: #ffffff;
            font-weight: 600;
            padding: 0.75rem 1.25rem;
            border-radius: 0.5rem;
            transition: background-color 0.15s ease;
        }
        .btn-cricbail:hover {
            background-color: #175240;
            color: #ffffff;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h2 class="fw-bold mb-1">🏏 CRIC<span class="brand-accent">BAIL</span></h2>
            <p class="text-white-50 small mb-0">Secure Administrator Portal</p>
        </div>

        <div class="p-4 p-md-5">
            @if(session('status'))
                <div class="alert alert-success alert-dismissible fade show small mb-4" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show small mb-4" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.submit') }}" novalidate>
                @csrf

                <!-- Email Address -->
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold small text-secondary">Email Address</label>
                    <input type="email" 
                           class="form-control @error('email') is-invalid @enderror" 
                           id="email" 
                           name="email" 
                           value="{{ old('email') }}" 
                           required 
                           autofocus 
                           placeholder="admin@cricbail.com">
                    @error('email')
                        <div class="invalid-feedback small">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold small text-secondary">Password</label>
                    <input type="password" 
                           class="form-control @error('password') is-invalid @enderror" 
                           id="password" 
                           name="password" 
                           required 
                           placeholder="••••••••">
                    @error('password')
                        <div class="invalid-feedback small">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="mb-4 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label small text-secondary" for="remember">Keep me logged in</label>
                </div>

                <!-- Submit Button -->
                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-cricbail">
                        Sign In to Dashboard
                    </button>
                </div>

                <div class="text-center">
                    <a href="{{ url('/') }}" class="text-decoration-none small text-muted">
                        &larr; Return to public site
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts (Local) -->
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
