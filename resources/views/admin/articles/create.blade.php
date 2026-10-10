@extends('admin.layouts.admin')

@section('title', 'Write New Cricket Article - CricBail Admin')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">Write New Cricket Article</h1>
                <p class="text-secondary mb-0 small">Publish breaking match reports, tournament analyses, player spotlights, or editorial op-eds.</p>
            </div>
            <a href="{{ route('admin.articles.index') }}" class="btn btn-outline-secondary btn-sm">
                &larr; Back to Articles
            </a>
        </div>

        <div class="card admin-card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
            <form method="POST" action="{{ route('admin.articles.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="row g-3">
                    <!-- Title -->
                    <div class="col-12">
                        <label for="title" class="form-label fw-bold">Article Title / Headline <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title" class="form-control form-control-lg @error('title') is-invalid @enderror" value="{{ old('title') }}" placeholder="e.g. Jasprit Bumrah Masters Reverse Swing to Dismantle Top Order" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Slug & Category -->
                    <div class="col-md-6">
                        <label for="slug" class="form-label fw-semibold">Custom Slug (Optional)</label>
                        <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}" placeholder="auto-generated-from-title-if-blank">
                        <div class="form-text small">Leave blank to auto-generate a clean URL slug.</div>
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="category_id" class="form-label fw-bold">News Category <span class="text-danger">*</span></label>
                        <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                            <option value="" disabled {{ old('category_id') ? '' : 'selected' }}>Select Category...</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ (string)old('category_id') === (string)$cat->id ? 'selected' : '' }}>
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
                        <textarea name="excerpt" id="excerpt" rows="2" class="form-control @error('excerpt') is-invalid @enderror" placeholder="A concise 1-2 sentence lead summarizing the story for card teasers and meta descriptions...">{{ old('excerpt') }}</textarea>
                        @error('excerpt')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Featured Image Upload -->
                    <div class="col-12">
                        <label for="featured_image" class="form-label fw-bold">Featured Image (Upload)</label>
                        <input type="file" name="featured_image" id="featured_image" class="form-control @error('featured_image') is-invalid @enderror" accept="image/jpeg,image/png,image/jpg,image/webp">
                        <div class="form-text small">Supported formats: JPEG, JPG, PNG, WEBP. Maximum file size: 2MB. Filename is securely hashed upon upload.</div>
                        @error('featured_image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Article Body Content -->
                    <div class="col-12">
                        <label for="content" class="form-label fw-bold">Article Content (HTML / Text) <span class="text-danger">*</span></label>
                        <textarea name="content" id="content" rows="12" class="form-control @error('content') is-invalid @enderror" placeholder="Full article body content. Supports paragraphs and standard formatting..." required>{{ old('content') }}</textarea>
                        @error('content')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Status & Publication Date -->
                    <div class="col-md-6">
                        <label for="status" class="form-label fw-bold">Publication Status <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="published" {{ old('status', 'published') === 'published' ? 'selected' : '' }}>Published (Live immediately)</option>
                            <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Draft (Save without publishing)</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="published_at" class="form-label fw-semibold">Publication Date (Optional)</label>
                        <input type="datetime-local" name="published_at" id="published_at" class="form-control @error('published_at') is-invalid @enderror" value="{{ old('published_at') }}">
                        <div class="form-text small">Defaults to current time if published and left blank.</div>
                        @error('published_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- SEO Meta Fields Accordion / Section -->
                    <div class="col-12 mt-4 pt-3 border-top">
                        <h5 class="h6 fw-bold text-dark mb-3">🔍 Search Engine Optimization (SEO Meta Tags)</h5>
                    </div>

                    <div class="col-md-6">
                        <label for="meta_title" class="form-label fw-semibold small">Meta Title (Max 100 chars)</label>
                        <input type="text" name="meta_title" id="meta_title" class="form-control form-control-sm @error('meta_title') is-invalid @enderror" value="{{ old('meta_title') }}" placeholder="Custom browser tab and search title">
                        @error('meta_title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="meta_description" class="form-label fw-semibold small">Meta Description (Max 255 chars)</label>
                        <input type="text" name="meta_description" id="meta_description" class="form-control form-control-sm @error('meta_description') is-invalid @enderror" value="{{ old('meta_description') }}" placeholder="Search engine snippet summary">
                        @error('meta_description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-12 d-flex justify-content-end gap-2 pt-4 border-top mt-4">
                        <a href="{{ route('admin.articles.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-success fw-bold px-4">
                            Save & Create Article
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
