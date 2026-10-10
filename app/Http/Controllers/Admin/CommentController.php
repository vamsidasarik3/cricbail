<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommentController extends Controller
{
    /**
     * Display a listing of comments for moderation.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');
        $articleId = $request->query('article_id');
        $search = $request->query('search');

        $query = Comment::with('article')->latest();

        // Status filtering
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        // Article filtering
        if ($articleId) {
            $query->where('article_id', $articleId);
        }

        // Keyword search (commenter name or body)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('visitor_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('comment_body', 'like', "%{$search}%");
            });
        }

        $comments = $query->paginate(10)->withQueryString();

        // Calculate counts for badges
        $counts = [
            'all' => Comment::count(),
            'pending' => Comment::pending()->count(),
            'approved' => Comment::approved()->count(),
            'rejected' => Comment::rejected()->count(),
        ];

        $articles = Article::has('comments')->orderBy('title')->get();

        return view('admin.comments.index', compact('comments', 'counts', 'status', 'articleId', 'articles', 'search'));
    }

    /**
     * Approve a pending or rejected comment.
     */
    public function approve(Comment $comment): RedirectResponse
    {
        $comment->update(['status' => 'approved']);

        return back()->with('status', "Comment #{$comment->id} by \"{$comment->visitor_name}\" has been approved and is now publicly visible.");
    }

    /**
     * Reject a pending or approved comment.
     */
    public function reject(Comment $comment): RedirectResponse
    {
        $comment->update(['status' => 'rejected']);

        return back()->with('status', "Comment #{$comment->id} by \"{$comment->visitor_name}\" has been rejected.");
    }

    /**
     * Permanently delete a comment.
     */
    public function destroy(Comment $comment): RedirectResponse
    {
        $author = $comment->visitor_name;
        $id = $comment->id;
        $comment->delete();

        return back()->with('status', "Comment #{$id} by \"{$author}\" has been permanently removed.");
    }
}
