@extends('admin.layouts.admin')

@section('title', 'Create New Quiz - CricBail Admin')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">Create Cricket Quiz</h1>
                <p class="text-secondary mb-0 small">Set up quiz title and description. You will add exactly 10 questions in the next step.</p>
            </div>
            <a href="{{ route('admin.quizzes.index') }}" class="btn btn-outline-secondary btn-sm">
                &larr; Back to Quizzes
            </a>
        </div>

        <div class="card admin-card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
            <form method="POST" action="{{ route('admin.quizzes.store') }}">
                @csrf

                <!-- Title -->
                <div class="mb-3">
                    <label for="title" class="form-label fw-bold">Quiz Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" placeholder="e.g. World Cup 2023 Master Trivia" required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Slug -->
                <div class="mb-3">
                    <label for="slug" class="form-label fw-semibold">URL Slug (Optional)</label>
                    <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}" placeholder="auto-generated from title if blank">
                    <div class="form-text small">Leave blank to automatically slugify the title.</div>
                    @error('slug')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Description -->
                <div class="mb-3">
                    <label for="description" class="form-label fw-semibold">Short Description / Briefing</label>
                    <textarea name="description" id="description" rows="3" class="form-control @error('description') is-invalid @enderror" placeholder="Briefly describe what this quiz tests (e.g., iconic finals, purple caps, etc.)">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Status Note -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Initial Status</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror">
                        <option value="draft" selected>Draft (Recommended - Add 10 questions first)</option>
                        <option value="published">Published (Blocked until 10 questions exist)</option>
                    </select>
                    <div class="form-text text-muted small mt-2">
                        ℹ️ <strong>Publication Rule:</strong> Every quiz requires exactly 10 questions with 4 multiple-choice options before it can be published.
                    </div>
                    @error('status')
                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Submit Button -->
                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <a href="{{ route('admin.quizzes.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-success fw-bold px-4">
                        Save Quiz & Add Questions &rarr;
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
