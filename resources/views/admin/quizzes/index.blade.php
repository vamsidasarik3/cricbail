@extends('admin.layouts.admin')

@section('title', 'Manage Cricket Quizzes - CricBail Admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Cricket Quizzes Management</h1>
        <p class="text-secondary mb-0 small">Create, edit, curate questions, and manage publication for 10-question, 120-second trivia.</p>
    </div>

    <a href="{{ route('admin.quizzes.create') }}" class="btn btn-success fw-bold d-flex align-items-center gap-2">
        <span>➕</span> Create New Quiz
    </a>
</div>

<!-- Quizzes Table Card -->
<div class="card admin-card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark">All Quizzes ({{ $quizzes->total() }})</h2>
        <span class="badge bg-light text-secondary border small">Requirement: Exactly 10 questions to publish</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-secondary small text-uppercase">
                <tr>
                    <th style="width: 32%;">Quiz Title & Slug</th>
                    <th style="width: 14%;">Questions</th>
                    <th style="width: 12%;">Attempts</th>
                    <th style="width: 12%;">Status</th>
                    <th style="width: 30%;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($quizzes as $quiz)
                <tr>
                    <td>
                        <div class="fw-bold text-dark">{{ $quiz->title }}</div>
                        <div class="small text-muted font-monospace">{{ $quiz->slug }}</div>
                        @if($quiz->published_at)
                            <div class="small text-muted">Published: {{ $quiz->published_at->format('M d, Y') }}</div>
                        @endif
                    </td>

                    <td>
                        @if($quiz->questions_count === 10)
                            <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 px-2.5 py-1">
                                ✓ 10 / 10 Questions
                            </span>
                        @else
                            <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50 px-2.5 py-1" title="Must have exactly 10 questions to publish">
                                ⚠️ {{ $quiz->questions_count }} / 10 Questions
                            </span>
                        @endif
                    </td>

                    <td>
                        <span class="badge bg-light text-dark border px-2.5 py-1">
                            📊 {{ $quiz->attempts_count }} {{ Str::plural('play', $quiz->attempts_count) }}
                        </span>
                    </td>

                    <td>
                        @if($quiz->status === 'published')
                            <span class="badge bg-success px-2.5 py-1">Published</span>
                        @else
                            <span class="badge bg-secondary px-2.5 py-1">Draft</span>
                        @endif
                    </td>

                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-1 flex-wrap">
                            <!-- Manage Questions -->
                            <a href="{{ route('admin.quizzes.questions.index', $quiz->id) }}" class="btn btn-sm btn-outline-primary" title="Manage Questions">
                                ❓ Questions ({{ $quiz->questions_count }})
                            </a>

                            <!-- Edit Quiz Info -->
                            <a href="{{ route('admin.quizzes.edit', $quiz->id) }}" class="btn btn-sm btn-outline-secondary" title="Edit Quiz Info">
                                ✏️ Edit
                            </a>

                            <!-- Publish / Unpublish Toggle -->
                            @if($quiz->status === 'draft')
                                <form method="POST" action="{{ route('admin.quizzes.publish', $quiz->id) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success" {{ $quiz->questions_count !== 10 ? 'disabled' : '' }} title="{{ $quiz->questions_count !== 10 ? 'Cannot publish: requires exactly 10 questions' : 'Publish Quiz' }}">
                                        🚀 Publish
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.quizzes.unpublish', $quiz->id) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-warning text-dark" title="Revert to Draft">
                                        ⏸️ Unpublish
                                    </button>
                                </form>
                            @endif

                            <!-- View Public Quiz -->
                            @if($quiz->status === 'published')
                                <a href="{{ route('quizzes.show', $quiz->slug) }}" target="_blank" class="btn btn-sm btn-outline-info" title="Preview Public Page">
                                    ↗
                                </a>
                            @endif

                            <!-- Delete Quiz -->
                            <form method="POST" action="{{ route('admin.quizzes.destroy', $quiz->id) }}" class="d-inline" onsubmit="return confirm('Delete this quiz and all its questions? This cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Quiz">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                        No quizzes found. Click <strong>Create New Quiz</strong> above to get started.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($quizzes->hasPages())
    <div class="card-footer bg-white py-3 border-top d-flex justify-content-center">
        {{ $quizzes->links() }}
    </div>
    @endif
</div>
@endsection
