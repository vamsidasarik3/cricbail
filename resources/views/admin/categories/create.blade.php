@extends('admin.layouts.admin')

@section('title', 'Add New Category - CricBail Admin')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">Add New Category</h1>
                <p class="text-secondary mb-0 small">Create a taxonomy section for grouping cricket stories.</p>
            </div>
            <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary btn-sm">
                &larr; Back to Categories
            </a>
        </div>

        <div class="card admin-card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
            <form method="POST" action="{{ route('admin.categories.store') }}">
                @csrf

                <!-- Name -->
                <div class="mb-3">
                    <label for="name" class="form-label fw-bold">Category Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control form-control-lg @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. World Cup 2027" required autofocus>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Slug -->
                <div class="mb-3">
                    <label for="slug" class="form-label fw-semibold">URL Slug (Optional)</label>
                    <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}" placeholder="world-cup-2027">
                    <div class="form-text small">Leave blank to automatically slugify the name.</div>
                    @error('slug')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <label for="description" class="form-label fw-semibold">Description (Optional)</label>
                    <textarea name="description" id="description" rows="4" class="form-control @error('description') is-invalid @enderror" placeholder="Brief summary describing the scope of this category...">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Form Controls -->
                <div class="d-flex justify-content-between align-items-center">
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-success fw-bold px-4">
                        💾 Create Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
