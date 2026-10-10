@extends('admin.layouts.admin')

@section('title', 'Manage Questions: ' . $quiz->title . ' - CricBail Admin')

@section('content')
<div class="mb-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.quizzes.index') }}">Quizzes</a></li>
            <li class="breadcrumb-item active">{{ Str::limit($quiz->title, 35) }}</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Questions for: {{ $quiz->title }}</h1>
            <div class="d-flex align-items-center gap-2">
                <span class="badge {{ $quiz->status === 'published' ? 'bg-success' : 'bg-secondary' }}">
                    Status: {{ ucfirst($quiz->status) }}
                </span>
                <span class="badge {{ $questions->count() === 10 ? 'bg-success' : 'bg-warning text-dark' }}">
                    {{ $questions->count() }} / 10 Questions
                </span>
                @if($quiz->status === 'published')
                    <a href="{{ route('quizzes.show', $quiz->slug) }}" target="_blank" class="small text-decoration-none">
                        View Public Page ↗
                    </a>
                @endif
            </div>
        </div>

        <div class="d-flex gap-2">
            @if($quiz->status === 'draft')
                <form method="POST" action="{{ route('admin.quizzes.publish', $quiz->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-success fw-bold" {{ $questions->count() !== 10 ? 'disabled' : '' }} title="{{ $questions->count() !== 10 ? 'Requires exactly 10 questions to publish' : 'Publish Quiz Now' }}">
                        🚀 Publish Quiz
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.quizzes.unpublish', $quiz->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning text-dark fw-bold">
                        ⏸️ Unpublish Quiz
                    </button>
                </form>
            @endif

            <a href="{{ route('admin.quizzes.index') }}" class="btn btn-outline-secondary">
                &larr; Back to Quizzes
            </a>
        </div>
    </div>
</div>

<!-- Publication Requirement Status Alert -->
@if($questions->count() === 10)
    <div class="alert alert-success d-flex align-items-center gap-2 py-2.5 mb-4 shadow-sm" role="alert">
        <span class="fs-5">✓</span>
        <div>
            <strong>Publication Ready:</strong> This quiz has exactly <strong>10 questions</strong>. All criteria for playable cricket quizzes are satisfied.
        </div>
    </div>
@else
    <div class="alert alert-warning d-flex align-items-center gap-2 py-2.5 mb-4 shadow-sm" role="alert">
        <span class="fs-5">⚠️</span>
        <div>
            <strong>Action Needed:</strong> A playable quiz requires <strong>exactly 10 questions</strong>. 
            Currently at <strong>{{ $questions->count() }}</strong> ({{ 10 - $questions->count() }} more needed to publish).
        </div>
    </div>
@endif

<div class="row g-4">
    <!-- Column 1: Add New Question Card -->
    <div class="col-lg-5">
        <div class="card admin-card border-0 shadow-sm rounded-4 p-4 sticky-top bg-white" style="top: 80px;">
            <h2 class="h5 fw-bold text-dark mb-3 pb-2 border-bottom">
                ➕ Add New Question
            </h2>

            <form method="POST" action="{{ route('admin.quizzes.questions.store', $quiz->id) }}">
                @csrf

                <!-- Question Text -->
                <div class="mb-3">
                    <label for="question_text" class="form-label fw-bold small">Question Text <span class="text-danger">*</span></label>
                    <textarea name="question_text" id="question_text" rows="3" class="form-control form-control-sm @error('question_text') is-invalid @enderror" placeholder="e.g. Who holds the record for the most centuries in World Cup history?" required>{{ old('question_text') }}</textarea>
                    @error('question_text')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- 4 Options -->
                <div class="row g-2 mb-3">
                    <div class="col-sm-6">
                        <label for="option_a" class="form-label fw-bold small">Option A <span class="text-danger">*</span></label>
                        <input type="text" name="option_a" id="option_a" class="form-control form-control-sm @error('option_a') is-invalid @enderror" value="{{ old('option_a') }}" required>
                        @error('option_a') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-sm-6">
                        <label for="option_b" class="form-label fw-bold small">Option B <span class="text-danger">*</span></label>
                        <input type="text" name="option_b" id="option_b" class="form-control form-control-sm @error('option_b') is-invalid @enderror" value="{{ old('option_b') }}" required>
                        @error('option_b') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-sm-6">
                        <label for="option_c" class="form-label fw-bold small">Option C <span class="text-danger">*</span></label>
                        <input type="text" name="option_c" id="option_c" class="form-control form-control-sm @error('option_c') is-invalid @enderror" value="{{ old('option_c') }}" required>
                        @error('option_c') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-sm-6">
                        <label for="option_d" class="form-label fw-bold small">Option D <span class="text-danger">*</span></label>
                        <input type="text" name="option_d" id="option_d" class="form-control form-control-sm @error('option_d') is-invalid @enderror" value="{{ old('option_d') }}" required>
                        @error('option_d') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <!-- Correct Answer Selector -->
                <div class="mb-3">
                    <label for="correct_option" class="form-label fw-bold small">Designate Correct Answer <span class="text-danger">*</span></label>
                    <select name="correct_option" id="correct_option" class="form-select form-select-sm @error('correct_option') is-invalid @enderror" required>
                        <option value="" disabled {{ old('correct_option') ? '' : 'selected' }}>Select correct option...</option>
                        <option value="a" {{ old('correct_option') === 'a' ? 'selected' : '' }}>Option A</option>
                        <option value="b" {{ old('correct_option') === 'b' ? 'selected' : '' }}>Option B</option>
                        <option value="c" {{ old('correct_option') === 'c' ? 'selected' : '' }}>Option C</option>
                        <option value="d" {{ old('correct_option') === 'd' ? 'selected' : '' }}>Option D</option>
                    </select>
                    @error('correct_option')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Explanation -->
                <div class="mb-3">
                    <label for="explanation" class="form-label fw-semibold small">Trivia Explanation & Fact (Shown after quiz completion)</label>
                    <textarea name="explanation" id="explanation" rows="2" class="form-control form-control-sm @error('explanation') is-invalid @enderror" placeholder="Context or historical trivia verifying the correct answer...">{{ old('explanation') }}</textarea>
                    @error('explanation')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Display Order -->
                <div class="mb-3">
                    <label for="display_order" class="form-label fw-semibold small">Display Order</label>
                    <input type="number" name="display_order" id="display_order" min="1" max="100" class="form-control form-control-sm" value="{{ old('display_order', $questions->count() + 1) }}">
                </div>

                <button type="submit" class="btn btn-success btn-sm fw-bold w-100 py-2">
                    ➕ Add Question to Quiz
                </button>
            </form>
        </div>
    </div>

    <!-- Column 2: Current Questions List -->
    <div class="col-lg-7">
        <div class="card admin-card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h2 class="h6 fw-bold mb-0 text-dark">Current Questions in Quiz ({{ $questions->count() }})</h2>
                <small class="text-muted">Display Order 1 to 10</small>
            </div>

            <div class="p-4">
                @if($questions->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <p class="mb-0">No questions added yet. Use the form on the left to add your first question.</p>
                    </div>
                @else
                    <div class="d-flex flex-column gap-3">
                        @foreach($questions as $index => $q)
                        <div class="p-3 rounded-3 border bg-light position-relative">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-dark fw-bold">
                                    #{{ $q->display_order }} (Question {{ $index + 1 }} of {{ $questions->count() }})
                                </span>

                                <div class="d-flex gap-1">
                                    <!-- Edit Button (trigger modal) -->
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" data-bs-toggle="modal" data-bs-target="#editQuestionModal{{ $q->id }}" title="Edit Question">
                                        ✏️ Edit
                                    </button>

                                    <!-- Delete Question -->
                                    <form method="POST" action="{{ route('admin.quizzes.questions.destroy', [$quiz->id, $q->id]) }}" class="d-inline" onsubmit="return confirm('Delete this question?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Delete Question">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="fw-bold text-dark mb-2">
                                {{ $q->question_text }}
                            </div>

                            <!-- Options Grid -->
                            <div class="row g-2 small mb-2">
                                <div class="col-sm-6">
                                    <div class="p-2 rounded border {{ $q->correct_option === 'a' ? 'bg-success bg-opacity-25 border-success text-success fw-bold' : 'bg-white text-dark' }}">
                                        A: {{ $q->option_a }} {{ $q->correct_option === 'a' ? '✓' : '' }}
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-2 rounded border {{ $q->correct_option === 'b' ? 'bg-success bg-opacity-25 border-success text-success fw-bold' : 'bg-white text-dark' }}">
                                        B: {{ $q->option_b }} {{ $q->correct_option === 'b' ? '✓' : '' }}
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-2 rounded border {{ $q->correct_option === 'c' ? 'bg-success bg-opacity-25 border-success text-success fw-bold' : 'bg-white text-dark' }}">
                                        C: {{ $q->option_c }} {{ $q->correct_option === 'c' ? '✓' : '' }}
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-2 rounded border {{ $q->correct_option === 'd' ? 'bg-success bg-opacity-25 border-success text-success fw-bold' : 'bg-white text-dark' }}">
                                        D: {{ $q->option_d }} {{ $q->correct_option === 'd' ? '✓' : '' }}
                                    </div>
                                </div>
                            </div>

                            @if(!empty($q->explanation))
                            <div class="small text-secondary bg-white p-2 rounded border mt-2">
                                💡 <strong>Explanation:</strong> {{ $q->explanation }}
                            </div>
                            @endif
                        </div>

                        <!-- Edit Modal for this question -->
                        <div class="modal fade" id="editQuestionModal{{ $q->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content rounded-4 border-0 shadow">
                                    <form method="POST" action="{{ route('admin.quizzes.questions.update', [$quiz->id, $q->id]) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header p-4 border-bottom">
                                            <h5 class="modal-title fw-bold text-dark">Edit Question #{{ $q->display_order }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold small">Question Text</label>
                                                <textarea name="question_text" rows="3" class="form-control" required>{{ $q->question_text }}</textarea>
                                            </div>

                                            <div class="row g-2 mb-3">
                                                <div class="col-sm-6">
                                                    <label class="form-label fw-bold small">Option A</label>
                                                    <input type="text" name="option_a" class="form-control form-control-sm" value="{{ $q->option_a }}" required>
                                                </div>
                                                <div class="col-sm-6">
                                                    <label class="form-label fw-bold small">Option B</label>
                                                    <input type="text" name="option_b" class="form-control form-control-sm" value="{{ $q->option_b }}" required>
                                                </div>
                                                <div class="col-sm-6">
                                                    <label class="form-label fw-bold small">Option C</label>
                                                    <input type="text" name="option_c" class="form-control form-control-sm" value="{{ $q->option_c }}" required>
                                                </div>
                                                <div class="col-sm-6">
                                                    <label class="form-label fw-bold small">Option D</label>
                                                    <input type="text" name="option_d" class="form-control form-control-sm" value="{{ $q->option_d }}" required>
                                                </div>
                                            </div>

                                            <div class="row g-2 mb-3">
                                                <div class="col-sm-6">
                                                    <label class="form-label fw-bold small">Correct Option</label>
                                                    <select name="correct_option" class="form-select form-select-sm" required>
                                                        <option value="a" {{ $q->correct_option === 'a' ? 'selected' : '' }}>Option A</option>
                                                        <option value="b" {{ $q->correct_option === 'b' ? 'selected' : '' }}>Option B</option>
                                                        <option value="c" {{ $q->correct_option === 'c' ? 'selected' : '' }}>Option C</option>
                                                        <option value="d" {{ $q->correct_option === 'd' ? 'selected' : '' }}>Option D</option>
                                                    </select>
                                                </div>

                                                <div class="col-sm-6">
                                                    <label class="form-label fw-bold small">Display Order</label>
                                                    <input type="number" name="display_order" min="1" max="100" class="form-control form-control-sm" value="{{ $q->display_order }}" required>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold small">Explanation Note</label>
                                                <textarea name="explanation" rows="2" class="form-control form-control-sm">{{ $q->explanation }}</textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer p-3 border-top justify-content-between">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-success fw-bold px-4">Update Question</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
