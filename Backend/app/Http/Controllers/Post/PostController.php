<?php

namespace App\Http\Controllers\Post;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Http\Resources\Post\PostResource;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with(['user:id,name,image', 'tags'])->withCount('comments')->latest()->paginate();

        return success_response(PostResource::pagination($posts));
    }

    public function store(StorePostRequest $request)
    {
        $data = $request->validated();

        $post = Post::create([
            'title'      => $data['title'],
            'body'       => $data['body'],
            'user_id'    => Auth::id(),
            'expires_at' => now()->addHours(24),
        ]);

        $tagIds = collect($data['tags'])->map(function ($tagName) {
            return Tag::firstOrCreate(['name' => strtolower(trim($tagName))])->id;
        });

        $post->tags()->sync($tagIds);

        return success_response(PostResource::item($post->load(['tags', 'user'])), 'Post created successfully', 201);
    }

    public function show(Post $post)
    {
        return success_response(PostResource::item($post->load(['user', 'tags'])->loadCount('comments')));
    }

    public function update(UpdatePostRequest $request, Post $post)
    {
        Gate::authorize('update', $post);

        $post->update($request->only(['title', 'body']));

        if ($request->has('tags')) {
            $tagIds = collect($request->tags)->map(function ($tagName) {
                return Tag::firstOrCreate(['name' => strtolower(trim($tagName))])->id;
            });

            $post->tags()->sync($tagIds);
        }

        return success_response(PostResource::item($post->load(['tags', 'user'])), 'Post updated successfully');
    }

    public function destroy(Post $post)
    {
        Gate::authorize('delete', $post);

        $post->delete();

        return success_response(null, 'Post deleted successfully');
    }
}
