<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function index(Request $request): View
    {
        $query = Faq::query()->orderBy('sort_order')->orderBy('id');

        if ($q = trim((string) $request->query('q'))) {
            $query->where(function ($sub) use ($q) {
                $sub->where('question', 'like', "%{$q}%")
                    ->orWhere('answer', 'like', "%{$q}%");
            });
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        $faqs = $query->paginate(15)->withQueryString();
        $categories = Faq::distinct()->pluck('category');

        return view('admin.faqs.index', compact('faqs', 'categories'));
    }

    public function create(): View
    {
        return view('admin.faqs.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'max:100'],
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Faq::create([
            'category' => trim($validated['category']),
            'question' => trim($validated['question']),
            'answer' => trim($validated['answer']),
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        return redirect()->route('admin.faqs.index')
            ->with('success', 'Đã thêm câu hỏi thường gặp thành công.');
    }

    public function edit(Faq $faq): View
    {
        return view('admin.faqs.edit', compact('faq'));
    }

    public function update(Request $request, Faq $faq): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'max:100'],
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $faq->update([
            'category' => trim($validated['category']),
            'question' => trim($validated['question']),
            'answer' => trim($validated['answer']),
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : false,
        ]);

        return redirect()->route('admin.faqs.index')
            ->with('success', 'Đã cập nhật câu hỏi thường gặp thành công.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        return redirect()->route('admin.faqs.index')
            ->with('success', 'Đã xóa câu hỏi thành công.');
    }
}
