@extends('admin.layouts.admin')

@section('title', 'Edit Article: ' . $article->title . ' - CricBail Admin')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">Edit Cricket Article</h1>
                <p class="text-secondary mb-0 small">Editing #{{ $article->id }}: <strong>{{ Str::limit($article->title, 40) }}</strong></p>
            </div>
            <div class="d-flex gap-2">
                @if($article->status === 'published')
                <a href="{{ route('news.show', $article->slug) }}" target="_blank" class="btn btn-outline-info btn-sm">
                    ↗ Preview Live Page
                </a>
                @endif
                <a href="{{ route('admin.articles.index') }}" class="btn btn-outline-secondary btn-sm">
                    &larr; Back to Articles
                </a>
            </div>
        </div>

        <div class="card admin-card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
            <form method="POST" action="{{ route('admin.articles.update', $article->id) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <!-- Title -->
                    <div class="col-12">
                        <label for="title" class="form-label fw-bold">Article Title / Headline <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title" class="form-control form-control-lg @error('title') is-invalid @enderror" value="{{ old('title', $article->title) }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Slug & Category -->
                    <div class="col-md-6">
                        <label for="slug" class="form-label fw-semibold">URL Slug <span class="text-danger">*</span></label>
                        <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $article->slug) }}" required>
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="category_id" class="form-label fw-bold">News Category <span class="text-danger">*</span></label>
                        <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ (string)old('category_id', $article->category_id) === (string)$cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Excerpt -->
                    <div class="col-12">
                        <label for="excerpt" class="form-label fw-semibold">Excerpt / Standfirst</label>
                        <textarea name="excerpt" id="excerpt" rows="2" class="form-control @error('excerpt') is-invalid @enderror">{{ old('excerpt', $article->excerpt) }}</textarea>
                        @error('excerpt')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Featured Image Preview and Replacement -->
                    <div class="col-12">
                        <label for="featured_image" class="form-label fw-bold">Featured Image</label>
                        
                        @if($article->featured_image)
                        <div class="mb-2 d-flex align-items-center gap-3 p-2 border rounded bg-light">
                            <img src="{{ asset($article->featured_image) }}" alt="Current featured image" class="rounded object-fit-cover" style="width: 100px; height: 60px;">
                            <div class="small text-muted">
                                <div>Current image: <code>{{ $article->featured_image }}</code></div>
                                <div>Upload a new image below if you wish to replace it.</div>
                            </div>
                        </div>
                        @endif

                        <input type="file" name="featured_image" id="featured_image" class="form-control @error('featured_image') is-invalid @enderror" accept="image/jpeg,image/png,image/jpg,image/webp">
                        <div class="form-text small">Accepted formats: JPEG, JPG, PNG, WEBP. Maximum: 2MB. Leaves existing image intact if left unselected.</div>
                        @error('featured_image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Article Body Content -->
                    <div class="col-12">
                        <label for="content" class="form-label fw-bold">Article Content (HTML / Text) <span class="text-danger">*</span></label>
                        <textarea name="content" id="content" rows="12" class="form-control @error('content') is-invalid @enderror" required>{{ old('content', $article->content) }}</textarea>
                        @error('content')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Status & Publication Date -->
                    <div class="col-md-6">
                        <label for="status" class="form-label fw-bold">Publication Status <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="published" {{ old('status', $article->status) === 'published' ? 'selected' : '' }}>Published (Publicly visible)</option>
                            <option value="draft" {{ old('status', $article->status) === 'draft' ? 'selected' : '' }}>Draft (Private)</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="published_at" class="form-label fw-semibold">Publication Date</label>
                        <input type="datetime-local" name="published_at" id="published_at" class="form-control @error('published_at') is-invalid @enderror" value="{{ old('published_at', $article->published_at ? $article->published_at->format('Y-m-d\TH:i') : '') }}">
                        @error('published_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- SEO Meta Fields -->
                    <div class="col-12 mt-4 pt-3 border-top">
                        <h5 class="h6 fw-bold text-dark mb-3">🔍 Search Engine Optimization (SEO Meta Tags)</h5>
                    </div>

                    <div class="col-md-6">
                        <label for="meta_title" class="form-label fw-semibold small">Meta Title</label>
                        <input type="text" name="meta_title" id="meta_title" class="form-control form-control-sm @error('meta_title') is-invalid @enderror" value="{{ old('meta_title', $article->meta_title) }}">
                        @error('meta_title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="meta_description" class="form-label fw-semibold small">Meta Description</label>
                        <input type="text" name="meta_description" id="meta_description" class="form-control form-control-sm @error('meta_description') is-invalid @enderror" value="{{ old('meta_description', $article->meta_description) }}">
                        @error('meta_description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-12 d-flex justify-content-end gap-2 pt-4 border-top mt-4">
                        <a href="{{ route('admin.articles.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-success fw-bold px-4">
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
