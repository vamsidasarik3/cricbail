@extends('layouts.app')

@section('title', App\Services\SeoService::title('Contact Editorial Team'))
@section('meta_description', 'Connect with the CricBail editorial desk, submit match commentary feedback, or send story inquiries.')
@section('canonical_url', App\Services\SeoService::canonicalUrl('contact'))

@push('schema')
<script type="application/ld+json">
{!! json_encode(App\Services\SeoService::breadcrumbSchema([
    ['name' => 'Home', 'url' => App\Services\SeoService::canonicalUrl('/')],
    ['name' => 'Contact Us', 'url' => App\Services\SeoService::canonicalUrl('contact')]
]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
@endpush

@section('content')
<div class="container py-3">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-success text-decoration-none">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Contact Us</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="bg-white p-4 p-md-5 rounded-4 shadow-sm border border-light mb-5">
                <span class="badge bg-success bg-opacity-10 text-success fw-bold px-3 py-1.5 text-uppercase mb-2">Editorial Desk</span>
                <h1 class="display-6 fw-bold text-dark mb-2">Contact CricBail</h1>
                <p class="text-secondary mb-4">
                    Send us your feedback, article suggestions, or corrections. Our correspondents review all inquiries.
                </p>

                @if(session('status'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                <form method="POST" action="{{ route('contact.submit') }}" novalidate>
                    @csrf

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label small fw-semibold text-secondary">Your Full Name *</label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   required 
                                   placeholder="e.g. Vamsi Krishna">
                            @error('name')
                            <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label small fw-semibold text-secondary">Email Address *</label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email') }}" 
                                   required 
                                   placeholder="name@example.com">
                            @error('email')
                            <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="subject" class="form-label small fw-semibold text-secondary">Subject / Topic *</label>
                        <input type="text" 
                               class="form-control @error('subject') is-invalid @enderror" 
                               id="subject" 
                               name="subject" 
                               value="{{ old('subject') }}" 
                               required 
                               placeholder="e.g. Match Analysis Feedback or Correction">
                        @error('subject')
                        <div class="invalid-feedback small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="message" class="form-label small fw-semibold text-secondary">Your Message *</label>
                        <textarea class="form-control @error('message') is-invalid @enderror" 
                                  id="message" 
                                  name="message" 
                                  rows="5" 
                                  required 
                                  placeholder="Write your note or feedback to the CricBail editorial desk...">{{ old('message') }}</textarea>
                        @error('message')
                        <div class="invalid-feedback small">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-success fw-semibold px-4 py-2">
                        Send Message to Editorial Team
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
