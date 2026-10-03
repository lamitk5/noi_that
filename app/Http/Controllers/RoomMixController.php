<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CartService;
use App\Support\DimensionFit;
use App\Support\FurnitureGlb;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class RoomMixController extends Controller
{
    private const ROOMS = [
        'khach' => ['label' => 'Phòng khách', 'width' => 5.0, 'depth' => 4.0, 'height' => 2.8],
        'ngu' => ['label' => 'Phòng ngủ', 'width' => 4.0, 'depth' => 3.5, 'height' => 2.7],
        'an' => ['label' => 'Phòng ăn', 'width' => 4.5, 'depth' => 3.5, 'height' => 2.8],
        'custom' => ['label' => 'Tự nhập', 'width' => 4.0, 'depth' => 4.0, 'height' => 2.8],
    ];

    public function __construct(protected CartService $cartService)
    {
    }

    public function show(Request $request): View
    {
        $products = Product::query()
            ->active()
            ->with(['category:id,name', 'primaryImage', 'variants'])
            ->orderBy('name')
            ->get();

        $catalog = $products->map(fn (Product $product) => $this->catalogEntry($product))
            ->filter(fn (array $entry) => $entry['sizes'] !== [])
            ->values();

        $layout = session('room_mix');
        if (! is_array($layout) || ($layout['version'] ?? 0) !== 2) {
            $layout = ['version' => 2, 'room' => 'khach', 'width' => null, 'depth' => null, 'floor' => 'oak', 'wall' => '#f5f0e8', 'items' => []];
        }

        return view('rooms.mix', [
            'catalog' => $catalog,
            'layout' => $layout,
            'rooms' => self::ROOMS,
            'focusProduct' => $request->integer('product') ?: null,
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        session(['room_mix' => $this->layout($request)]);

        return back()->with('success', 'Đã lưu bố cục phòng.');
    }

    public function buy(Request $request): RedirectResponse
    {
        $layout = $this->layout($request);
        session(['room_mix' => $layout]);

        if ($layout['items'] === []) {
            return back()->with('error', 'Hãy thêm ít nhất một món vào phòng trước khi mua.');
        }

        $this->cartService->setBuyNowMode(false);
        $added = 0;
        $errors = [];

        foreach ($layout['items'] as $item) {
            $product = Product::query()->with('variants')->find($item['product_id']);
            if (! $product) {
                continue;
            }

            $variant = $item['variant_id'] ? $product->variants->firstWhere('id', $item['variant_id']) : null;

            try {
                $this->cartService->add($product, 1, $variant);
                $added++;
            } catch (InvalidArgumentException $e) {
                $errors[] = $product->name.': '.$e->getMessage();
            }
        }

        if ($added === 0) {
            return back()->with('error', $errors ? implode(' ', $errors) : 'Không thêm được sản phẩm vào giỏ.');
        }

        $message = "Đã thêm {$added} sản phẩm trong phòng vào giỏ.";
        if ($errors) {
            $message .= ' '.implode(' ', $errors);
        }

        return redirect()->route('cart.index')->with('success', $message);
    }

    /**
     * One entry per product, with every size the shop sells so the planner
     * can swap a 160 cm bed for a 180 cm one and see the real footprint.
     */
    private function catalogEntry(Product $product): array
    {
        [$shape] = FurnitureGlb::measure($product->name, $product->dimensions);
        $fallback = FurnitureGlb::box($product->name, $product->dimensions);

        $sizes = $product->variants
            ->sortByDesc(fn ($variant) => $variant->stock > 0)
            ->groupBy(fn ($variant) => trim((string) $variant->size) ?: 'default')
            ->map(function ($variants, $label) use ($fallback, $product) {
                $variant = $variants->first();
                $box = DimensionFit::parse($variant->size) ?? $fallback;

                return [
                    'variant_id' => $variant->id,
                    'label' => $label === 'default'
                        ? FurnitureGlb::displaySize($product->name, $product->dimensions)
                        : $label,
                    'price' => (float) $variant->final_price,
                    'in_stock' => $variants->contains(fn ($v) => $v->stock > 0),
                    'box' => [
                        'l' => max(5, round((float) $box['l'], 1)),
                        'w' => max(2, round((float) $box['w'], 1)),
                        'h' => max(2, round((float) $box['h'], 1)),
                    ],
                ];
            })
            ->sortBy(fn ($size) => $size['box']['l'] * $size['box']['w'])
            ->values()
            ->all();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'category' => $product->category?->name ?? 'Khác',
            'image' => $product->primary_image_url,
            'url' => route('products.show', $product->slug),
            'shape' => $shape,
            'sizes' => $sizes,
        ];
    }

    /**
     * @return array{version: int, room: string, width: ?float, depth: ?float, floor: string, wall: string, items: array<int, array{product_id: int, variant_id: ?int, x: float, y: float, rotation: int}>}
     */
    private function layout(Request $request): array
    {
        $data = $request->validate([
            'room' => ['required', 'in:'.implode(',', array_keys(self::ROOMS))],
            'width' => ['nullable', 'numeric', 'min:1.5', 'max:15'],
            'depth' => ['nullable', 'numeric', 'min:1.5', 'max:15'],
            'floor' => ['nullable', 'in:oak,walnut,stone'],
            'wall' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'items' => ['nullable', 'array', 'max:30'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.variant_id' => ['nullable', 'integer'],
            'items.*.x' => ['required', 'numeric', 'min:0', 'max:1500'],
            'items.*.y' => ['required', 'numeric', 'min:0', 'max:1500'],
            'items.*.rotation' => ['nullable', 'integer', 'in:0,90,180,270'],
        ]);

        $items = collect($data['items'] ?? [])
            ->map(fn (array $item) => [
                'product_id' => (int) $item['product_id'],
                'variant_id' => isset($item['variant_id']) ? (int) $item['variant_id'] : null,
                'x' => round((float) $item['x'], 1),
                'y' => round((float) $item['y'], 1),
                'rotation' => (int) ($item['rotation'] ?? 0),
            ])
            ->values()
            ->all();

        return [
            'version' => 2,
            'room' => $data['room'],
            'width' => isset($data['width']) ? (float) $data['width'] : null,
            'depth' => isset($data['depth']) ? (float) $data['depth'] : null,
            'floor' => $data['floor'] ?? 'oak',
            'wall' => $data['wall'] ?? '#f5f0e8',
            'items' => $items,
        ];
    }
}
