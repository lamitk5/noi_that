<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function index(Request $request): View
    {
        $query = Faq::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id');

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($q = trim((string) $request->query('q'))) {
            $query->where(function ($sub) use ($q) {
                $sub->where('question', 'like', "%{$q}%")
                    ->orWhere('answer', 'like', "%{$q}%");
            });
        }

        $faqs = $query->get();

        $categories = Faq::where('is_active', true)
            ->distinct()
            ->pluck('category');

        return view('faq.index', compact('faqs', 'categories'));
    }
}
