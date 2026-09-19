<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $query = Post::query()->with(['category', 'author'])->latest();

        if ($q = trim((string) $request->query('q'))) {
            $query->where('title', 'like', "%{$q}%");
        }

        if ($catId = $request->query('category_id')) {
            $query->where('post_category_id', $catId);
        }

        $posts = $query->paginate(15)->withQueryString();
        $categories = PostCategory::where('is_active', true)->get();

        return view('admin.posts.index', compact('posts', 'categories'));
    }

    public function create(): View
    {
        $categories = PostCategory::where('is_active', true)->get();

        return view('admin.posts.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:posts,slug'],
            'post_category_id' => ['nullable', 'exists:post_categories,id'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $slug = ! empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['title']);

        // Ensure unique slug
        $baseSlug = $slug;
        $counter = 1;
        while (Post::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $isPublished = $request->has('is_published') ? $request->boolean('is_published') : true;

        Post::create([
            'post_category_id' => $validated['post_category_id'] ?? null,
            'author_id' => $request->user()->id,
            'title' => trim($validated['title']),
            'slug' => $slug,
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'],
            'featured_image' => $validated['featured_image'] ?? null,
            'is_published' => $isPublished,
            'published_at' => $isPublished ? now() : null,
        ]);

        return redirect()->route('admin.posts.index')
            ->with('success', 'Đã xuất bản bài viết thành công.');
    }

    public function edit(Post $post): View
    {
        $categories = PostCategory::where('is_active', true)->get();

        return view('admin.posts.edit', compact('post', 'categories'));
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('posts', 'slug')->ignore($post->id)],
            'post_category_id' => ['nullable', 'exists:post_categories,id'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $isPublished = $request->has('is_published') ? $request->boolean('is_published') : false;

        $post->update([
            'post_category_id' => $validated['post_category_id'] ?? null,
            'title' => trim($validated['title']),
            'slug' => trim($validated['slug']),
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'],
            'featured_image' => $validated['featured_image'] ?? null,
            'is_published' => $isPublished,
            'published_at' => ($isPublished && ! $post->published_at) ? now() : $post->published_at,
        ]);

        return redirect()->route('admin.posts.index')
            ->with('success', 'Đã cập nhật bài viết thành công.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $post->delete();

        return redirect()->route('admin.posts.index')
            ->with('success', 'Đã xóa bài viết thành công.');
    }
}
