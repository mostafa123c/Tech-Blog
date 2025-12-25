<?php

namespace App\Http\Controllers\Comment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\UpdateCommentRequest;
use App\Http\Resources\Comment\CommentResouce;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function index(Post $post)
    {
        $comments = $post->comments()->with('user:id,name,image')->latest()->paginate(10);

        return success_response(CommentResouce::pagination($comments));
    }
    public function store(StoreCommentRequest $request, Post $post)
    {
        $comment = $post->comments()->create([
            'body'    => $request->body,
            'user_id'=> Auth::id(),
        ]);

        return success_response(CommentResouce::item($comment->load('user:id,name,image')), 'Comment added successfully', 201);
    }

    public function update(UpdateCommentRequest $request, Comment $comment)
    {
        Gate::authorize('update', $comment);
        $comment->update([
            'body' => $request->body,
        ]);

        return success_response(CommentResouce::item($comment->load('user:id,name,image')), 'Comment updated successfully');
    }

    public function destroy(Comment $comment)
    {
        Gate::authorize('delete', $comment);

        $comment->delete();

        return success_response(null, 'Comment deleted successfully');
    }
}
