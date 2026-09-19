<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $query = Post::query()
            ->with(['category', 'author'])
            ->where('is_published', true)
            ->latest('published_at');

        if ($categorySlug = $request->query('category')) {
            $query->whereHas('category', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug)->where('is_active', true);
            });
        }

        if ($q = trim((string) $request->query('q'))) {
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('excerpt', 'like', "%{$q}%");
            });
        }

        $posts = $query->paginate(9)->withQueryString();
        $categories = PostCategory::where('is_active', true)->get();

        return view('posts.index', compact('posts', 'categories'));
    }

    public function show(string $slug): View
    {
        $post = Post::where('slug', $slug)
            ->where('is_published', true)
            ->with(['category', 'author'])
            ->firstOrFail();

        // Increment view count atomically
        $post->increment('view_count');

        $relatedPosts = Post::where('id', '!=', $post->id)
            ->where('is_published', true)
            ->when($post->post_category_id, function ($q) use ($post) {
                $q->where('post_category_id', $post->post_category_id);
            })
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('posts.show', compact('post', 'relatedPosts'));
    }
}
