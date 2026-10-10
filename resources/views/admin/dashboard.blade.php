@extends('admin.layouts.admin')

@section('title', 'Admin Dashboard - CricBail Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Administration Overview</h1>
        <p class="text-secondary mb-0 small">Cricket news publication, quizzes, and editorial moderation control panel.</p>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('admin.articles.create') }}" class="btn btn-sm btn-success fw-bold">
            ✍️ New Article
        </a>
        <a href="{{ route('admin.quizzes.create') }}" class="btn btn-sm btn-primary fw-bold">
            🧠 New Quiz
        </a>
    </div>
</div>

<!-- Primary Summary Metrics Cards (Required by Prompt) -->
<div class="row g-3 mb-4">
    <!-- 1. Total Published Articles -->
    <div class="col-sm-6 col-xl-2">
        <div class="admin-card p-3 h-100 position-relative border-start border-4 border-success">
            <div class="d-flex justify-content-between align-items-center text-muted mb-1">
                <span class="small fw-semibold">PUBLISHED NEWS</span>
                <span class="fs-6">📰</span>
            </div>
            <h3 class="fw-bold mb-1 text-success">{{ $metrics['published_articles'] }}</h3>
            <a href="{{ route('admin.articles.index', ['status' => 'published']) }}" class="stretched-link small text-decoration-none text-muted">
                View Published &rarr;
            </a>
        </div>
    </div>

    <!-- 2. Draft Articles -->
    <div class="col-sm-6 col-xl-2">
        <div class="admin-card p-3 h-100 position-relative border-start border-4 border-secondary">
            <div class="d-flex justify-content-between align-items-center text-muted mb-1">
                <span class="small fw-semibold">DRAFT ARTICLES</span>
                <span class="fs-6">📝</span>
            </div>
            <h3 class="fw-bold mb-1 text-secondary">{{ $metrics['draft_articles'] }}</h3>
            <a href="{{ route('admin.articles.index', ['status' => 'draft']) }}" class="stretched-link small text-decoration-none text-muted">
                View Drafts &rarr;
            </a>
        </div>
    </div>

    <!-- 3. Categories -->
    <div class="col-sm-6 col-xl-2">
        <div class="admin-card p-3 h-100 position-relative border-start border-4 border-info">
            <div class="d-flex justify-content-between align-items-center text-muted mb-1">
                <span class="small fw-semibold">CATEGORIES</span>
                <span class="fs-6">📁</span>
            </div>
            <h3 class="fw-bold mb-1 text-dark">{{ $metrics['categories_count'] }}</h3>
            <a href="{{ route('admin.categories.index') }}" class="stretched-link small text-decoration-none text-muted">
                Manage Groups &rarr;
            </a>
        </div>
    </div>

    <!-- 4. Pending Comments -->
    <div class="col-sm-6 col-xl-2">
        <div class="admin-card p-3 h-100 position-relative border-start border-4 border-warning">
            <div class="d-flex justify-content-between align-items-center text-muted mb-1">
                <span class="small fw-semibold">PENDING COMMENTS</span>
                <span class="fs-6">💬</span>
            </div>
            <h3 class="fw-bold mb-1 text-warning">{{ $metrics['pending_comments'] }}</h3>
            <a href="{{ route('admin.comments.index', ['status' => 'pending']) }}" class="stretched-link small text-decoration-none text-muted">
                Moderate &rarr;
            </a>
        </div>
    </div>

    <!-- 5. Published Quizzes -->
    <div class="col-sm-6 col-xl-2">
        <div class="admin-card p-3 h-100 position-relative border-start border-4 border-primary">
            <div class="d-flex justify-content-between align-items-center text-muted mb-1">
                <span class="small fw-semibold">ACTIVE QUIZZES</span>
                <span class="fs-6">🧠</span>
            </div>
            <h3 class="fw-bold mb-1 text-primary">{{ $metrics['published_quizzes'] }}</h3>
            <a href="{{ route('admin.quizzes.index') }}" class="stretched-link small text-decoration-none text-muted">
                Manage Quizzes &rarr;
            </a>
        </div>
    </div>

    <!-- 6. Quiz Attempts -->
    <div class="col-sm-6 col-xl-2">
        <div class="admin-card p-3 h-100 position-relative border-start border-4 border-dark">
            <div class="d-flex justify-content-between align-items-center text-muted mb-1">
                <span class="small fw-semibold">TOTAL ATTEMPTS</span>
                <span class="fs-6">🎯</span>
            </div>
            <h3 class="fw-bold mb-1 text-dark">{{ $metrics['quiz_attempts_count'] }}</h3>
            <a href="{{ route('admin.quizzes.index') }}" class="stretched-link small text-decoration-none text-muted">
                View Activity &rarr;
            </a>
        </div>
    </div>
</div>

<!-- Recent Articles & Comments Row -->
<div class="row g-4 mb-4">
    <!-- Recent Articles Table -->
    <div class="col-lg-7">
        <div class="admin-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 text-dark">📰 Recent Articles</h5>
                <a href="{{ route('admin.articles.index') }}" class="small text-decoration-none">View All Articles &rarr;</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentArticles as $art)
                        <tr>
                            <td>
                                <div class="fw-bold text-dark text-truncate" style="max-width: 250px;">{{ $art->title }}</div>
                                <div class="text-muted small">{{ $art->created_at->format('M d, Y') }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border">{{ $art->category ? $art->category->name : 'None' }}</span>
                            </td>
                            <td>
                                @if($art->status === 'published')
                                    <span class="badge bg-success">Published</span>
                                @else
                                    <span class="badge bg-secondary">Draft</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.articles.edit', $art->id) }}" class="btn btn-sm btn-outline-secondary py-0 px-2">Edit</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-3 text-muted">No articles found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Comments Table -->
    <div class="col-lg-5">
        <div class="admin-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 text-dark">💬 Recent Reader Comments</h5>
                <a href="{{ route('admin.comments.index') }}" class="small text-decoration-none">All Comments &rarr;</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Commenter</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentComments as $comm)
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">{{ $comm->visitor_name }}</div>
                                <div class="text-muted text-truncate small" style="max-width: 180px;">{{ Str::limit($comm->comment_body, 40) }}</div>
                            </td>
                            <td>
                                @if($comm->status === 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @elseif($comm->status === 'rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.comments.index', ['search' => $comm->visitor_name]) }}" class="btn btn-sm btn-outline-secondary py-0 px-2">Review</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center py-3 text-muted">No comments submitted yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Recent Quiz Attempts Row -->
<div class="row g-4">
    <div class="col-12">
        <div class="admin-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 text-dark">🎯 Recent 120-Second Quiz Attempts</h5>
                <a href="{{ route('admin.quizzes.index') }}" class="small text-decoration-none">Manage Quizzes &rarr;</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Quiz Title</th>
                            <th>Attempt Token</th>
                            <th>Score</th>
                            <th>Time Taken</th>
                            <th>Status</th>
                            <th>Date & Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentAttempts as $attempt)
                        <tr>
                            <td class="fw-bold text-dark">{{ $attempt->quiz ? $attempt->quiz->title : 'Deleted Quiz' }}</td>
                            <td><code>{{ substr($attempt->attempt_token, 0, 8) }}...</code></td>
                            <td>
                                <span class="badge {{ $attempt->score >= 8 ? 'bg-success' : ($attempt->score >= 5 ? 'bg-warning text-dark' : 'bg-danger') }}">
                                    {{ $attempt->score }} / 10
                                </span>
                            </td>
                            <td>{{ $attempt->formatted_time_taken }}</td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ ucfirst($attempt->status) }}</span>
                            </td>
                            <td class="text-muted">{{ $attempt->created_at->format('M d, Y &bull; g:i A') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-3 text-muted">No quiz attempts recorded yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
