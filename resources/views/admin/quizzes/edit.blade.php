@extends('admin.layouts.admin')

@section('title', 'Edit Quiz: ' . $quiz->title . ' - CricBail Admin')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">Edit Quiz Details</h1>
                <p class="text-secondary mb-0 small">Update title, slug, and status. Questions count: <strong>{{ $quiz->questions_count }} / 10</strong></p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.quizzes.questions.index', $quiz->id) }}" class="btn btn-outline-primary btn-sm fw-bold">
                    ❓ Manage Questions ({{ $quiz->questions_count }})
                </a>
                <a href="{{ route('admin.quizzes.index') }}" class="btn btn-outline-secondary btn-sm">
                    &larr; Quizzes List
                </a>
            </div>
        </div>

        <div class="card admin-card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
            <form method="POST" action="{{ route('admin.quizzes.update', $quiz->id) }}">
                @csrf
                @method('PUT')

                <!-- Title -->
                <div class="mb-3">
                    <label for="title" class="form-label fw-bold">Quiz Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $quiz->title) }}" required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Slug -->
                <div class="mb-3">
                    <label for="slug" class="form-label fw-semibold">URL Slug <span class="text-danger">*</span></label>
                    <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $quiz->slug) }}" required>
                    @error('slug')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Description -->
                <div class="mb-3">
                    <label for="description" class="form-label fw-semibold">Short Description / Briefing</label>
                    <textarea name="description" id="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $quiz->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Status -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror">
                        <option value="draft" {{ old('status', $quiz->status) === 'draft' ? 'selected' : '' }}>
                            Draft
                        </option>
                        <option value="published" {{ old('status', $quiz->status) === 'published' ? 'selected' : '' }}>
                            Published (Requires exactly 10 questions)
                        </option>
                    </select>

                    <div class="form-text text-muted small mt-2">
                        @if($quiz->questions_count === 10)
                            <span class="text-success fw-bold">✓ This quiz has 10 questions and meets all publication requirements.</span>
                        @else
                            <span class="text-warning fw-bold">⚠️ Current question count is {{ $quiz->questions_count }}/10. Publishing will be blocked until exactly 10 questions exist.</span>
                        @endif
                    </div>

                    @error('status')
                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Submit Button -->
                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <a href="{{ route('admin.quizzes.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-success fw-bold px-4">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
