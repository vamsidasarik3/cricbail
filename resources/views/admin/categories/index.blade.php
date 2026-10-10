@extends('admin.layouts.admin')

@section('title', 'Manage News Categories - CricBail Admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Article Categories</h1>
        <p class="text-secondary mb-0 small">Organize cricket coverage across tournaments, formats, domestic circuits, and features.</p>
    </div>
    <a href="{{ route('admin.categories.create') }}" class="btn btn-success fw-bold d-md-none">
        + New Category
    </a>
</div>

<div class="row g-4">
    <!-- Left Column: Categories List Table -->
    <div class="col-lg-7">
        <div class="card admin-card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h2 class="h6 fw-bold mb-0 text-dark">Existing Categories ({{ $categories->total() }})</h2>
                <span class="badge bg-light text-secondary border">
                    Sorted Alphabetically
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th style="width: 35%;">Name & Slug</th>
                            <th style="width: 35%;">Description</th>
                            <th style="width: 15%;" class="text-center">Articles</th>
                            <th style="width: 15%;" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">{{ $category->name }}</div>
                                <div class="font-monospace text-muted small"><code>/category/{{ $category->slug }}</code></div>
                            </td>
                            <td>
                                <span class="text-secondary small">
                                    {{ Str::limit($category->description ?? 'No description provided', 60) }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($category->articles_count > 0)
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 rounded-pill">
                                        {{ $category->articles_count }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1 rounded-pill">
                                        0
                                    </span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.categories.edit', $category->id) }}" class="btn btn-outline-secondary" title="Edit Category">
                                        ✏️
                                    </a>

                                    <form method="POST" action="{{ route('admin.categories.destroy', $category->id) }}" class="d-inline"
                                          onsubmit="return confirm('{{ $category->articles_count > 0 ? "Warning: This category contains {$category->articles_count} articles and deletion will be prevented by relational constraints." : "Are you sure you want to delete category \'{$category->name}\'?" }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Delete Category" {{ $category->articles_count > 0 ? 'data-bs-toggle=tooltip title="Contains articles"' : '' }}>
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                No categories found. Create one using the form on the right.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($categories->hasPages())
            <div class="card-footer bg-white border-top py-3">
                <div class="d-flex justify-content-center">
                    {{ $categories->links() }}
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Right Column: Create Category Card -->
    <div class="col-lg-5">
        <div class="card admin-card border-0 shadow-sm rounded-4 p-4 bg-white sticky-top" style="top: 80px;">
            <h2 class="h5 fw-bold text-dark mb-3">Add New Category</h2>
            <p class="text-secondary small mb-4">
                Categories are used to group cricket articles and power the main website navigation filters.
            </p>

            <form method="POST" action="{{ route('admin.categories.store') }}">
                @csrf

                <!-- Name -->
                <div class="mb-3">
                    <label for="name" class="form-label fw-bold">Category Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. ICC Tournaments" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Slug (Optional) -->
                <div class="mb-3">
                    <label for="slug" class="form-label fw-semibold">URL Slug (Optional)</label>
                    <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}" placeholder="icc-tournaments">
                    <div class="form-text small">Leave empty to generate automatically from name.</div>
                    @error('slug')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <label for="description" class="form-label fw-semibold">Description (Optional)</label>
                    <textarea name="description" id="description" rows="3" class="form-control @error('description') is-invalid @enderror" placeholder="Brief summary of what articles belong in this section...">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-success w-100 fw-bold py-2 shadow-sm">
                    💾 Save Category
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
