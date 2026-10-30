<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class PostApiController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return PostResource::collection(
            Post::query()->with('author')->latest()->paginate(10),
        );
    }

    public function store(Request $request): PostResource
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $post = $request->user()->posts()->create($validated);

        return new PostResource($post->load('author'));
    }

    public function show(Post $post): PostResource
    {
        return new PostResource($post->load('author'));
    }

    public function update(Request $request, Post $post): PostResource
    {
        Gate::authorize('update', $post);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'body' => ['sometimes', 'required', 'string'],
        ]);

        $post->update($validated);

        return new PostResource($post->load('author'));
    }

    public function destroy(Post $post): JsonResponse
    {
        Gate::authorize('delete', $post);
        $post->delete();

        return response()->json(status: 204);
    }
}
