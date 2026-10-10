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

                    <!-- Source Attribution -->
                    <div class="col-12 mt-4 pt-3 border-top">
                        <h5 class="h6 fw-bold text-dark mb-1">📰 Editorial Source Attribution (Journalistic Transparency)</h5>
                        <p class="text-muted small mb-3">If reporting on press releases, official cricket boards (BCCI, ICC, CA), or news agency wire reports, provide source attribution to maintain journalistic credibility.</p>
                    </div>

                    <div class="col-md-6">
                        <label for="source_name" class="form-label fw-semibold small">Source Name / Wire Attribution</label>
                        <input type="text" name="source_name" id="source_name" class="form-control form-control-sm @error('source_name') is-invalid @enderror" value="{{ old('source_name', $article->source_name) }}" placeholder="e.g. BCCI Press Release, ICC Media, ESPNCricinfo">
                        @error('source_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="source_url" class="form-label fw-semibold small">Source URL (Link to original release)</label>
                        <input type="url" name="source_url" id="source_url" class="form-control form-control-sm @error('source_url') is-invalid @enderror" value="{{ old('source_url', $article->source_url) }}" placeholder="https://www.bcci.tv/articles/...">
                        @error('source_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- SEO Meta Fields -->
                    <div class="col-12 mt-4 pt-3 border-top">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h5 class="h6 fw-bold text-dark mb-0">🔍 Search Engine Optimization (SEO Meta Tags)</h5>
                            <span class="badge bg-light text-secondary border small">Production Domain: {{ config('seo.canonical_domain', 'https://www.cricbail.com') }}</span>
                        </div>
                        <p class="text-muted small mb-3">Optimize how this article appears on Google, Bing, WhatsApp, X (Twitter), and Facebook.</p>
                    </div>

                    <div class="col-md-6">
                        <div class="d-flex justify-content-between">
                            <label for="meta_title" class="form-label fw-semibold small">Meta Title</label>
                            <span class="small text-muted" id="meta-title-counter">0 / 60-70 chars</span>
                        </div>
                        <input type="text" name="meta_title" id="meta_title" class="form-control form-control-sm @error('meta_title') is-invalid @enderror" value="{{ old('meta_title', $article->meta_title) }}" placeholder="Custom title (defaults to headline if empty)">
                        <div class="form-text small">Brand suffix <code>| CricBail</code> is appended automatically without duplication.</div>
                        @error('meta_title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <div class="d-flex justify-content-between">
                            <label for="meta_description" class="form-label fw-semibold small">Meta Description</label>
                            <span class="small text-muted" id="meta-desc-counter">0 / 120-160 chars</span>
                        </div>
                        <input type="text" name="meta_description" id="meta_description" class="form-control form-control-sm @error('meta_description') is-invalid @enderror" value="{{ old('meta_description', $article->meta_description) }}" placeholder="Search engine snippet summary (defaults to excerpt)">
                        <div class="form-text small">Optimal length: 140–160 characters for maximum search snippet visibility.</div>
                        @error('meta_description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Editorial SEO Health & SERP Preview Card -->
                    <div class="col-12 mt-3">
                        <div class="card border rounded-3 p-3 bg-light">
                            <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                                <span>🌐 Live Google SERP Snippet Preview</span>
                            </h6>
                            <div class="p-3 bg-white rounded border mb-3" style="max-width: 650px;">
                                <div class="text-muted small mb-1" style="font-size: 13px;">
                                    <span class="text-dark fw-bold">CricBail</span> <span class="text-muted">&rsaquo; news &rsaquo; <span id="serp-slug">{{ $article->slug }}</span></span>
                                </div>
                                <div class="h5 mb-1 text-primary fw-medium text-decoration-underline" id="serp-title" style="cursor: pointer; color: #1a0dab !important;">
                                    {{ $article->title }} | CricBail
                                </div>
                                <div class="text-secondary small" id="serp-desc" style="font-size: 13px; line-height: 1.4;">
                                    {{ $article->meta_description ?: $article->excerpt }}
                                </div>
                            </div>

                            <h6 class="fw-bold text-dark mb-2">📋 Editorial Quality & SEO Checklist</h6>
                            <div class="row g-2 small" id="seo-checklist">
                                <div class="col-md-6">
                                    <div class="p-2 rounded bg-white border d-flex align-items-center gap-2" id="chk-title">
                                        <span class="badge bg-secondary" id="chk-title-icon">?</span>
                                        <span><strong>Headline:</strong> <span id="chk-title-msg">Enter a clear, descriptive headline</span></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-2 rounded bg-white border d-flex align-items-center gap-2" id="chk-desc">
                                        <span class="badge bg-secondary" id="chk-desc-icon">?</span>
                                        <span><strong>Description:</strong> <span id="chk-desc-msg">Add excerpt or meta description</span></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-2 rounded bg-white border d-flex align-items-center gap-2" id="chk-content">
                                        <span class="badge bg-secondary" id="chk-content-icon">?</span>
                                        <span><strong>Content Depth:</strong> <span id="chk-content-msg">0 words (aim for 300+ words)</span></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-2 rounded bg-white border d-flex align-items-center gap-2" id="chk-image">
                                        <span class="badge bg-secondary" id="chk-image-icon">?</span>
                                        <span><strong>Featured Image:</strong> <span id="chk-image-msg">Upload an image for rich previews</span></span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2 text-muted" style="font-size: 11px;">
                                ⚠️ <em>Editorial Note:</em> These are advisory quality signals to help create thorough, original cricket coverage. Rankings are determined by search engines based on overall content value, accuracy, and user engagement.
                            </div>
                        </div>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    const excerptInput = document.getElementById('excerpt');
    const contentInput = document.getElementById('content');
    const metaTitleInput = document.getElementById('meta_title');
    const metaDescInput = document.getElementById('meta_description');
    const featuredImgInput = document.getElementById('featured_image');
    const hasExistingImage = {{ $article->featured_image ? 'true' : 'false' }};

    const serpTitle = document.getElementById('serp-title');
    const serpSlug = document.getElementById('serp-slug');
    const serpDesc = document.getElementById('serp-desc');
    const metaTitleCounter = document.getElementById('meta-title-counter');
    const metaDescCounter = document.getElementById('meta-desc-counter');

    function updatePreview() {
        const headline = titleInput.value.trim() || 'Article Headline';
        const metaTitle = metaTitleInput.value.trim();
        const displayTitle = (metaTitle || headline).replace(/\s*\|\s*CricBail\s*$/i, '') + ' | CricBail';
        serpTitle.textContent = displayTitle;

        const slug = slugInput.value.trim() || '{{ $article->slug }}';
        serpSlug.textContent = slug;

        const excerpt = excerptInput.value.trim();
        const metaDesc = metaDescInput.value.trim();
        const displayDesc = metaDesc || excerpt || 'Article summary will appear here in search engine snippets.';
        serpDesc.textContent = displayDesc;

        // Counters
        metaTitleCounter.textContent = `${metaTitle.length} chars (headline: ${headline.length})`;
        metaDescCounter.textContent = `${(metaDesc || excerpt).length} chars`;

        // Checklist evaluation
        // 1. Title
        const titleLen = headline.length;
        const chkTitleIcon = document.getElementById('chk-title-icon');
        const chkTitleMsg = document.getElementById('chk-title-msg');
        if (titleLen >= 30 && titleLen <= 70) {
            chkTitleIcon.className = 'badge bg-success';
            chkTitleIcon.textContent = '✓';
            chkTitleMsg.textContent = `${titleLen} chars (ideal length)`;
        } else if (titleLen > 0) {
            chkTitleIcon.className = 'badge bg-warning text-dark';
            chkTitleIcon.textContent = '!';
            chkTitleMsg.textContent = `${titleLen} chars (aim for 30-70 chars)`;
        } else {
            chkTitleIcon.className = 'badge bg-secondary';
            chkTitleIcon.textContent = '?';
            chkTitleMsg.textContent = 'Enter a clear, descriptive headline';
        }

        // 2. Description
        const descLen = (metaDesc || excerpt).length;
        const chkDescIcon = document.getElementById('chk-desc-icon');
        const chkDescMsg = document.getElementById('chk-desc-msg');
        if (descLen >= 120 && descLen <= 160) {
            chkDescIcon.className = 'badge bg-success';
            chkDescIcon.textContent = '✓';
            chkDescMsg.textContent = `${descLen} chars (ideal 120-160 chars)`;
        } else if (descLen > 0) {
            chkDescIcon.className = 'badge bg-warning text-dark';
            chkDescIcon.textContent = '!';
            chkDescMsg.textContent = `${descLen} chars (aim for 120-160 chars)`;
        } else {
            chkDescIcon.className = 'badge bg-secondary';
            chkDescIcon.textContent = '?';
            chkDescMsg.textContent = 'Add excerpt or meta description';
        }

        // 3. Word Count
        const text = contentInput.value.trim().replace(/<[^>]*>/g, '');
        const wordCount = text ? text.split(/\s+/).filter(Boolean).length : 0;
        const chkContentIcon = document.getElementById('chk-content-icon');
        const chkContentMsg = document.getElementById('chk-content-msg');
        if (wordCount >= 300) {
            chkContentIcon.className = 'badge bg-success';
            chkContentIcon.textContent = '✓';
            chkContentMsg.textContent = `${wordCount} words (solid depth)`;
        } else if (wordCount > 0) {
            chkContentIcon.className = 'badge bg-warning text-dark';
            chkContentIcon.textContent = '!';
            chkContentMsg.textContent = `${wordCount} words (thin content warning: aim for 300+)`;
        } else {
            chkContentIcon.className = 'badge bg-secondary';
            chkContentIcon.textContent = '?';
            chkContentMsg.textContent = '0 words (aim for 300+ words)';
        }

        // 4. Featured Image
        const chkImageIcon = document.getElementById('chk-image-icon');
        const chkImageMsg = document.getElementById('chk-image-msg');
        if ((featuredImgInput && featuredImgInput.files.length > 0) || hasExistingImage) {
            chkImageIcon.className = 'badge bg-success';
            chkImageIcon.textContent = '✓';
            chkImageMsg.textContent = 'Featured image present';
        } else {
            chkImageIcon.className = 'badge bg-warning text-dark';
            chkImageIcon.textContent = '!';
            chkImageMsg.textContent = 'Upload an image for rich search/social cards';
        }
    }

    [titleInput, slugInput, excerptInput, contentInput, metaTitleInput, metaDescInput].forEach(el => {
        if (el) el.addEventListener('input', updatePreview);
    });
    if (featuredImgInput) featuredImgInput.addEventListener('change', updatePreview);

    updatePreview();
});
</script>
@endsection
