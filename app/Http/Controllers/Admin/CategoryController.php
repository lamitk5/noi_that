<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $categories = Category::withCount('products')
            ->latest()
            ->paginate(15);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);
        }

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.create');
    }

    public function store(CategoryRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $data = $request->validated();
            $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
            $data['is_active'] = $request->boolean('is_active', true);

            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('categories', 'public');
            }

            $category = Category::create($data);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tạo danh mục thành công!',
                    'data' => $category,
                ], 201);
            }

            return redirect()->route('admin.categories.index')->with('success', 'Tạo danh mục mới thành công!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Category store failed', ['error' => $e->getMessage()]);
            $message = 'Không thể tạo danh mục: '.$e->getMessage();

            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $message], 500)
                : back()->withInput()->with('error', $message);
        }
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse|JsonResponse
    {
        try {
            $data = $request->validated();
            $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
            $data['is_active'] = $request->boolean('is_active', true);

            if ($request->hasFile('image')) {
                if ($category->image && Storage::disk('public')->exists($category->image)) {
                    Storage::disk('public')->delete($category->image);
                }
                $data['image'] = $request->file('image')->store('categories', 'public');
            }

            $category->update($data);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cập nhật danh mục thành công!',
                    'data' => $category,
                ]);
            }

            return redirect()->route('admin.categories.index')->with('success', 'Cập nhật danh mục thành công!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Category update failed', ['id' => $category->id, 'error' => $e->getMessage()]);
            $message = 'Cập nhật danh mục thất bại: '.$e->getMessage();

            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $message], 500)
                : back()->withInput()->with('error', $message);
        }
    }

    public function destroy(Request $request, Category $category): RedirectResponse|JsonResponse
    {
        try {
            if ($category->image && Storage::disk('public')->exists($category->image)) {
                Storage::disk('public')->delete($category->image);
            }

            $category->delete();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Đã xóa danh mục thành công.',
                ]);
            }

            return redirect()->route('admin.categories.index')->with('success', 'Đã xóa danh mục thành công.');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Category destroy failed', ['id' => $category->id, 'error' => $e->getMessage()]);
            $message = 'Không thể xóa danh mục: '.$e->getMessage();

            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $message], 500)
                : back()->with('error', $message);
        }
    }
}
