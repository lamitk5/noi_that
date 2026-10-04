<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CompareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CompareController extends Controller
{
    public function __construct(protected CompareService $compare) {}

    public function index(): View
    {
        $products = $this->compare->products(detailed: true);

        return view('compare.index', [
            'products' => $products,
            'rows' => $this->rows($products),
            'max' => CompareService::MAX,
        ]);
    }

    public function toggle(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $result = $this->compare->toggle($product);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => ! $result['limited'],
                'limited' => $result['limited'],
                'in_compare' => $result['in_compare'],
                'message' => $result['message'],
                'count' => $result['count'],
                'product_id' => $product->id,
                'item' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image' => $product->primary_image_url,
                    'toggle_url' => route('compare.toggle', $product),
                ],
            ], $result['limited'] ? 422 : 200);
        }

        return redirect()->back()->with($result['limited'] ? 'error' : 'success', $result['message']);
    }

    public function clear(Request $request): JsonResponse|RedirectResponse
    {
        $this->compare->clear();
        $message = 'Đã xóa hết sản phẩm trong bảng so sánh.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'count' => 0,
            ]);
        }

        return redirect()->route('compare.index')->with('success', $message);
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return list<array{label: string, cells: list<array{text: string, differs: bool, best: bool, badge: ?string}>}>
     */
    private function rows(Collection $products): array
    {
        if ($products->isEmpty()) {
            return [];
        }

        $specs = $products->map(function (Product $product) {
            $product->variants->each(fn ($variant) => $variant->setRelation('product', $product));

            $variantPrices = $product->variants
                ->map(fn ($variant) => (float) $variant->final_price)
                ->filter(fn (float $price) => $price > 0);
            $price = $variantPrices->isNotEmpty()
                ? (float) $variantPrices->min()
                : (float) $product->final_price;

            $colors = $product->variants->pluck('color')->map(fn ($value) => trim((string) $value))->filter()->unique()->values();
            if ($colors->isEmpty() && filled($product->color)) {
                $colors = collect([$product->color]);
            }

            $sizes = $product->variants->pluck('size')->map(fn ($value) => trim((string) $value))->filter()->unique()->values();
            $ratingCount = (int) ($product->rating_count ?? 0);
            $rating = $ratingCount > 0 ? round((float) $product->rating_avg, 1) : null;

            $priceLabel = ($variantPrices->unique()->count() > 1 ? 'Từ ' : '').number_format($price, 0, ',', '.').'₫';
            if ($product->is_on_sale) {
                $priceLabel .= ' · đang giảm giá';
            }

            return [
                'price' => round($price),
                'price_label' => $priceLabel,
                'rating' => $rating,
                'rating_label' => $rating === null ? 'Chưa có đánh giá' : number_format($rating, 1).' / 5 ('.$ratingCount.' đánh giá)',
                'category' => $product->category?->name ?: '—',
                'material' => filled($product->material) ? trim((string) $product->material) : '—',
                'dimensions' => filled($product->dimensions) ? $product->dimensions : '—',
                'sizes' => $sizes->isNotEmpty() ? $sizes->implode(', ') : '—',
                'colors' => $colors->isNotEmpty() ? $colors->implode(', ') : '—',
                'weight' => $product->weight ? number_format((float) $product->weight, 1, ',', '.').' kg' : '—',
                'stock' => $product->totalStock(),
                'stock_label' => $product->stockStatusText(),
                'sku' => $product->sku ?: '—',
                'summary' => filled($product->short_description) ? Str::limit(trim($product->short_description), 180) : '—',
            ];
        });

        $definitions = [
            ['label' => 'Giá', 'text' => 'price_label', 'score' => 'price', 'best' => 'min', 'badge' => 'Thấp nhất'],
            ['label' => 'Đánh giá', 'text' => 'rating_label', 'score' => 'rating', 'best' => 'max', 'badge' => 'Cao nhất'],
            ['label' => 'Danh mục', 'text' => 'category'],
            ['label' => 'Chất liệu', 'text' => 'material'],
            ['label' => 'Kích thước', 'text' => 'dimensions'],
            ['label' => 'Size có sẵn', 'text' => 'sizes'],
            ['label' => 'Màu sắc', 'text' => 'colors'],
            ['label' => 'Khối lượng', 'text' => 'weight'],
            ['label' => 'Tồn kho', 'text' => 'stock_label', 'score' => 'stock', 'best' => 'max', 'badge' => 'Còn nhiều hơn'],
            ['label' => 'Mã SKU', 'text' => 'sku'],
            ['label' => 'Mô tả ngắn', 'text' => 'summary'],
        ];

        return array_map(function (array $definition) use ($specs) {
            return [
                'label' => $definition['label'],
                'cells' => $this->cells(
                    $specs,
                    $definition['text'],
                    $definition['score'] ?? null,
                    $definition['best'] ?? null,
                    $definition['badge'] ?? null,
                ),
            ];
        }, $definitions);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $specs
     * @return list<array{text: string, differs: bool, best: bool, badge: ?string}>
     */
    private function cells(Collection $specs, string $textKey, ?string $scoreKey, ?string $direction, ?string $badge): array
    {
        $labels = $specs->pluck($textKey)->map(fn ($value) => (string) $value)->all();
        $differs = count(array_unique($labels)) > 1;
        $bestValue = null;

        if ($scoreKey && $direction) {
            $scores = $specs->pluck($scoreKey)->filter(fn ($value) => $value !== null);
            if ($scores->count() > 1 && $scores->unique()->count() > 1) {
                $bestValue = $direction === 'min' ? $scores->min() : $scores->max();
            }
        }

        return $specs->map(function (array $spec) use ($textKey, $scoreKey, $bestValue, $badge, $differs) {
            $isBest = $bestValue !== null
                && $scoreKey
                && $spec[$scoreKey] !== null
                && (float) $spec[$scoreKey] === (float) $bestValue;

            return [
                'text' => (string) $spec[$textKey],
                'differs' => $differs,
                'best' => $isBest,
                'badge' => $isBest ? $badge : null,
            ];
        })->all();
    }
}
