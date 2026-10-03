<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class RoomMixController extends Controller
{
    public function __construct(protected CartService $cartService)
    {
    }

    public function show(): View
    {
        $products = Product::query()
            ->active()
            ->with(['category:id,name', 'primaryImage', 'variants'])
            ->orderBy('name')
            ->limit(40)
            ->get();

        $layout = session('room_mix', ['room' => 'khach', 'items' => []]);

        return view('rooms.mix', [
            'products' => $products,
            'layout' => $layout,
            'rooms' => [
                'khach' => [
                    'label' => 'Phòng khách',
                    'image' => 'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=1600&q=80',
                ],
                'ngu' => [
                    'label' => 'Phòng ngủ',
                    'image' => 'https://images.unsplash.com/photo-1616594039964-ae9021a400a0?auto=format&fit=crop&w=1600&q=80',
                ],
            ],
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        session(['room_mix' => $this->layout($request)]);

        return back()->with('success', 'Đã lưu bố cục combo.');
    }

    public function buy(Request $request): RedirectResponse
    {
        $layout = $this->layout($request);
        session(['room_mix' => $layout]);

        if ($layout['items'] === []) {
            return back()->with('error', 'Hãy kéo ít nhất một món vào phòng trước khi mua.');
        }

        $this->cartService->setBuyNowMode(false);
        $added = 0;
        $errors = [];

        foreach ($layout['items'] as $item) {
            $product = Product::query()->find($item['product_id']);
            if (! $product) {
                continue;
            }

            try {
                $this->cartService->add($product, 1);
                $added++;
            } catch (InvalidArgumentException $e) {
                $errors[] = $product->name.': '.$e->getMessage();
            }
        }

        if ($added === 0) {
            return back()->with('error', $errors ? implode(' ', $errors) : 'Không thêm được sản phẩm vào giỏ.');
        }

        $message = "Đã thêm {$added} sản phẩm trong combo vào giỏ.";
        if ($errors) {
            $message .= ' '.implode(' ', $errors);
        }

        return redirect()->route('cart.index')->with('success', $message);
    }

    /**
     * @return array{room: string, items: array<int, array{product_id: int, x: float, y: float}>}
     */
    private function layout(Request $request): array
    {
        $data = $request->validate([
            'room' => ['required', 'in:khach,ngu'],
            'items' => ['nullable', 'array', 'max:12'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.x' => ['required', 'numeric', 'min:0', 'max:100'],
            'items.*.y' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $items = collect($data['items'] ?? [])
            ->map(fn (array $item) => [
                'product_id' => (int) $item['product_id'],
                'x' => round((float) $item['x'], 1),
                'y' => round((float) $item['y'], 1),
            ])
            ->unique('product_id')
            ->values()
            ->all();

        return [
            'room' => $data['room'],
            'items' => $items,
        ];
    }
}
