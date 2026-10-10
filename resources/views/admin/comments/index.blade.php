@extends('admin.layouts.admin')

@section('title', 'Comment Moderation - CricBail Admin')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
    <div>
        <h1 class="h2 fw-bold text-dark mb-1">💬 Comment Moderation</h1>
        <p class="text-muted mb-0 small">Review and moderate user discussions across cricket articles.</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-semibold">
            ● {{ $counts['pending'] }} Comments Awaiting Review
        </span>
    </div>
</div>

<!-- Filter Pills by Status -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div class="btn-group" role="group" aria-label="Status filters">
        <a href="{{ route('admin.comments.index', ['status' => 'all', 'article_id' => $articleId, 'search' => $search]) }}" 
           class="btn btn-sm {{ $status === 'all' ? 'btn-dark fw-bold' : 'btn-outline-secondary' }}">
            All <span class="badge bg-secondary ms-1">{{ $counts['all'] }}</span>
        </a>
        <a href="{{ route('admin.comments.index', ['status' => 'pending', 'article_id' => $articleId, 'search' => $search]) }}" 
           class="btn btn-sm {{ $status === 'pending' ? 'btn-warning text-dark fw-bold' : 'btn-outline-warning text-dark' }}">
            Pending <span class="badge bg-warning text-dark ms-1">{{ $counts['pending'] }}</span>
        </a>
        <a href="{{ route('admin.comments.index', ['status' => 'approved', 'article_id' => $articleId, 'search' => $search]) }}" 
           class="btn btn-sm {{ $status === 'approved' ? 'btn-success fw-bold' : 'btn-outline-success' }}">
            Approved <span class="badge bg-success ms-1">{{ $counts['approved'] }}</span>
        </a>
        <a href="{{ route('admin.comments.index', ['status' => 'rejected', 'article_id' => $articleId, 'search' => $search]) }}" 
           class="btn btn-sm {{ $status === 'rejected' ? 'btn-danger fw-bold' : 'btn-outline-danger' }}">
            Rejected <span class="badge bg-danger ms-1">{{ $counts['rejected'] }}</span>
        </a>
    </div>

    <!-- Filter by Article & Search Form -->
    <form method="GET" action="{{ route('admin.comments.index') }}" class="d-flex flex-wrap gap-2 align-items-center">
        <input type="hidden" name="status" value="{{ $status }}">
        
        <select name="article_id" class="form-select form-select-sm" style="max-width: 260px;" onchange="this.form.submit()">
            <option value="">All Articles ({{ $articles->count() }})</option>
            @foreach($articles as $art)
                <option value="{{ $art->id }}" {{ $articleId == $art->id ? 'selected' : '' }}>
                    {{ Str::limit($art->title, 35) }}
                </option>
            @endforeach
        </select>

        <div class="input-group input-group-sm" style="max-width: 220px;">
            <input type="text" name="search" class="form-control" placeholder="Search name/body..." value="{{ $search }}">
            <button class="btn btn-outline-secondary" type="submit">🔍</button>
        </div>

        @if($articleId || $search || $status !== 'all')
        <a href="{{ route('admin.comments.index') }}" class="btn btn-sm btn-link text-muted text-decoration-none">
            Reset
        </a>
        @endif
    </form>
</div>

<!-- Comments List Table -->
<div class="admin-card overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-uppercase text-secondary">
                <tr>
                    <th style="width: 18%;">Commenter</th>
                    <th style="width: 38%;">Comment Body</th>
                    <th style="width: 22%;">Target Article</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 12%;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($comments as $comm)
                <tr>
                    <!-- Commenter info -->
                    <td>
                        <div class="fw-bold text-dark">{{ $comm->visitor_name }}</div>
                        <div class="small text-muted text-truncate" title="{{ $comm->email }}">
                            ✉️ {{ $comm->email }}
                        </div>
                        <div class="small text-muted">
                            🕒 {{ $comm->created_at->format('M d, Y &bull; g:i A') }}
                        </div>
                    </td>

                    <!-- Comment text (safely rendered) -->
                    <td>
                        <div class="p-2.5 bg-light rounded-2 small text-dark border-start border-3 {{ $comm->status === 'approved' ? 'border-success' : ($comm->status === 'rejected' ? 'border-danger' : 'border-warning') }}">
                            {{ $comm->comment_body }}
                        </div>
                    </td>

                    <!-- Target Article -->
                    <td>
                        @if($comm->article)
                        <div class="fw-semibold small">
                            <a href="{{ route('news.show', $comm->article->slug) }}" target="_blank" class="text-dark text-decoration-none">
                                {{ Str::limit($comm->article->title, 45) }} ↗
                            </a>
                        </div>
                        <span class="badge bg-light text-secondary border small">
                            {{ $comm->article->category ? $comm->article->category->name : 'Article' }}
                        </span>
                        @else
                        <span class="text-danger small">Orphaned Comment</span>
                        @endif
                    </td>

                    <!-- Status Badge -->
                    <td>
                        @if($comm->status === 'approved')
                            <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 px-2.5 py-1">
                                Approved
                            </span>
                        @elseif($comm->status === 'rejected')
                            <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25 px-2.5 py-1">
                                Rejected
                            </span>
                        @else
                            <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50 px-2.5 py-1">
                                Pending
                            </span>
                        @endif
                    </td>

                    <!-- Action Buttons -->
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-1">
                            @if($comm->status !== 'approved')
                            <form method="POST" action="{{ route('admin.comments.approve', $comm->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-success" title="Approve Comment">
                                    ✓ Approve
                                </button>
                            </form>
                            @endif

                            @if($comm->status !== 'rejected')
                            <form method="POST" action="{{ route('admin.comments.reject', $comm->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-warning text-dark" title="Reject Comment">
                                    ✗ Reject
                                </button>
                            </form>
                            @endif

                            <form method="POST" action="{{ route('admin.comments.destroy', $comm->id) }}" onsubmit="return confirm('Permanently delete this comment? This cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Comment">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                        <div class="fs-4 mb-2">💬</div>
                        <h6>No comments match your filter criteria.</h6>
                        <p class="small mb-0">Select "All" or change your search filter to review discussions.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
@if($comments->hasPages())
<div class="d-flex justify-content-center">
    {{ $comments->links('pagination::bootstrap-5') }}
</div>
@endif
@endsection
