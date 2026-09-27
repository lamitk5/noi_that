<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = Review::with(['product', 'user'])
            ->latest();

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'approved') {
                $query->where('is_approved', true);
            } elseif ($status === 'pending') {
                $query->where('is_approved', false);
            }
        }

        if ($request->filled('rating')) {
            $query->where('rating', (int) $request->input('rating'));
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $query->where(function ($builder) use ($q) {
                $builder->where('comment', 'like', "%{$q}%")
                    ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$q}%"));
            });
        }

        $reviews = $query->paginate(15)->withQueryString();
        $stats = [
            'total' => Review::count(),
            'approved' => Review::where('is_approved', true)->count(),
            'pending' => Review::where('is_approved', false)->count(),
        ];

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $reviews, 'stats' => $stats]);
        }

        return view('admin.reviews.index', compact('reviews', 'stats'));
    }

    public function approve(Review $review): RedirectResponse|JsonResponse
    {
        $review->is_approved = true;
        $review->save();
        $message = 'Đã duyệt đánh giá thành công.';

        return request()->wantsJson()
            ? response()->json(['success' => true, 'message' => $message])
            : back()->with('success', $message);
    }

    public function hide(Review $review): RedirectResponse|JsonResponse
    {
        $review->is_approved = false;
        $review->save();
        $message = 'Đã ẩn đánh giá khỏi trang sản phẩm.';

        return request()->wantsJson()
            ? response()->json(['success' => true, 'message' => $message])
            : back()->with('success', $message);
    }

    public function destroy(Review $review): RedirectResponse|JsonResponse
    {
        try {
            $review->delete();
            $message = 'Đã xóa đánh giá thành công.';
        } catch (\Throwable $e) {
            report($e);
            $message = 'Không thể xóa đánh giá: '.$e->getMessage();

            return request()->wantsJson()
                ? response()->json(['success' => false, 'message' => $message], 500)
                : back()->with('error', $message);
        }

        return request()->wantsJson()
            ? response()->json(['success' => true, 'message' => $message])
            : back()->with('success', $message);
    }
}
