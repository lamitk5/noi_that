<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuotationController extends Controller
{
    public function create(): View
    {
        $products = Product::query()
            ->active()
            ->with('primaryImage')
            ->orderBy('name')
            ->get();

        return view('quotes.create', compact('products'));
    }

    public function store(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'project_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:80'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:0', 'max:99'],
        ], [
            'project_name.required' => 'Vui lòng nhập tên dự án.',
            'contact_name.required' => 'Vui lòng nhập người nhận báo giá.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'items.required' => 'Hãy chọn ít nhất một sản phẩm.',
        ]);

        $discount = (float) ($data['discount'] ?? 0);
        $lines = collect($data['items'])
            ->filter(fn (array $row) => (int) $row['quantity'] > 0)
            ->map(function (array $row) {
                $product = Product::query()->with('primaryImage')->find($row['product_id']);
                $qty = (int) $row['quantity'];
                $price = (float) $product->final_price;

                return [
                    'product' => $product,
                    'quantity' => $qty,
                    'price' => $price,
                    'line' => $price * $qty,
                ];
            })
            ->filter(fn (array $line) => $line['product'] && $line['product']->is_active)
            ->values();

        if ($lines->isEmpty()) {
            return back()->withErrors(['items' => 'Hãy chọn ít nhất một sản phẩm đang bán.'])->withInput();
        }

        $subtotal = (float) $lines->sum('line');
        $discountAmount = round($subtotal * $discount / 100);
        $total = $subtotal - $discountAmount;

        return view('quotes.print', [
            'projectName' => $data['project_name'],
            'contactName' => $data['contact_name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'discount' => $discount,
            'lines' => $lines,
            'subtotal' => $subtotal,
            'discountAmount' => $discountAmount,
            'total' => $total,
            'quotedAt' => now(),
        ]);
    }
}
