@extends('admin.layouts.admin')

@section('title', 'Edit Category: ' . $category->name . ' - CricBail Admin')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">Edit Category</h1>
                <p class="text-secondary mb-0 small">Editing taxonomy section: <strong>{{ $category->name }}</strong></p>
            </div>
            <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary btn-sm">
                &larr; Back to Categories
            </a>
        </div>

        @if($category->articles_count > 0)
        <div class="alert alert-info d-flex align-items-center gap-2 mb-4 rounded-3 border-0 shadow-sm">
            <span>ℹ️</span>
            <div>
                This category currently contains <strong>{{ $category->articles_count }}</strong> associated {{ Str::plural('article', $category->articles_count) }}. 
                Articles will remain securely mapped to this category.
            </div>
        </div>
        @else
        <div class="alert alert-light border d-flex align-items-center gap-2 mb-4 rounded-3">
            <span>💡</span>
            <div>This category currently has no associated articles.</div>
        </div>
        @endif

        <div class="card admin-card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
            <form method="POST" action="{{ route('admin.categories.update', $category->id) }}">
                @csrf
                @method('PUT')

                <!-- Name -->
                <div class="mb-3">
                    <label for="name" class="form-label fw-bold">Category Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control form-control-lg @error('name') is-invalid @enderror" value="{{ old('name', $category->name) }}" required autofocus>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Slug -->
                <div class="mb-3">
                    <label for="slug" class="form-label fw-semibold">URL Slug <span class="text-danger">*</span></label>
                    <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $category->slug) }}" required>
                    <div class="form-text small">Used in article filter URLs: <code>/news?category={{ $category->slug }}</code></div>
                    @error('slug')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <label for="description" class="form-label fw-semibold">Description (Optional)</label>
                    <textarea name="description" id="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description', $category->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Controls -->
                <div class="d-flex justify-content-between align-items-center pt-2">
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary fw-bold px-4">
                        💾 Update Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
