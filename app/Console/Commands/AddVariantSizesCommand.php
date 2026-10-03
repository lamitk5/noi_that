<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\FurnitureGlb;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddVariantSizesCommand extends Command
{
    protected $signature = 'products:add-sizes {--dry-run : Chỉ in ra các size sẽ thêm}';

    protected $description = 'Thêm size nhỏ và lớn cho các sản phẩm mới có một kích thước, mỗi size đủ các màu hiện có';

    /**
     * Each plan is [label, length factor, depth factor, height factor];
     * the middle entry is the current catalog size.
     */
    private const PLANS = [
        'sofa' => [['2 chỗ', 0.8, 1, 1], ['3 chỗ', 1, 1, 1], ['4 chỗ', 1.2, 1, 1]],
        'bench' => [['Ngắn', 0.75, 1, 1], ['Tiêu chuẩn', 1, 1, 1], ['Dài', 1.35, 1, 1]],
        'screen' => [['3 tấm', 0.75, 1, 1], ['4 tấm', 1, 1, 1], ['5 tấm', 1.25, 1, 1]],
        'round-table' => [['Nhỏ', 0.8, 0.8, 1], ['Tiêu chuẩn', 1, 1, 1], ['Lớn', 1.25, 1.25, 1]],
        'rug' => [['Nhỏ', 0.75, 0.74, 1], ['Tiêu chuẩn', 1, 1, 1], ['Lớn', 1.25, 1.3, 1]],
        'mirror' => [['Nhỏ', 0.75, 1, 0.75], ['Tiêu chuẩn', 1, 1, 1], ['Lớn', 1.25, 1, 1.2]],
        'curtain' => [['Hẹp', 0.7, 1, 1], ['Tiêu chuẩn', 1, 1, 1], ['Rộng', 1.45, 1, 1]],
        'glass' => [['Hẹp', 0.9, 1, 1], ['Tiêu chuẩn', 1, 1, 1], ['Rộng', 1.35, 1, 1]],
        'tub' => [['Nhỏ', 0.88, 0.9, 1], ['Tiêu chuẩn', 1, 1, 1], ['Lớn', 1.06, 1.05, 1]],
        'shelf' => [['Nhỏ', 0.75, 1, 0.8], ['Tiêu chuẩn', 1, 1, 1], ['Lớn', 1.25, 1, 1.15]],
        'cabinet' => [['Nhỏ', 0.75, 1, 1], ['Tiêu chuẩn', 1, 1, 1], ['Lớn', 1.3, 1, 1]],
        'tv' => [['Nhỏ', 0.8, 1, 1], ['Tiêu chuẩn', 1, 1, 1], ['Lớn', 1.2, 1, 1]],
        'island' => [['Nhỏ', 0.8, 0.9, 1], ['Tiêu chuẩn', 1, 1, 1], ['Lớn', 1.25, 1.1, 1]],
        'nightstand' => [['Nhỏ', 0.85, 0.9, 0.9], ['Tiêu chuẩn', 1, 1, 1], ['Lớn', 1.2, 1.1, 1.1]],
        'table' => [['Nhỏ', 0.8, 0.9, 1], ['Tiêu chuẩn', 1, 1, 1], ['Lớn', 1.25, 1.1, 1]],
        'pendant' => [['Nhỏ', 0.75, 0.75, 0.75], ['Tiêu chuẩn', 1, 1, 1], ['Lớn', 1.3, 1.3, 1.3]],
        'floor-lamp' => [['Thấp', 1, 1, 0.85], ['Tiêu chuẩn', 1, 1, 1], ['Cao', 1.1, 1.1, 1.12]],
        'table-lamp' => [['Nhỏ', 0.8, 0.8, 0.8], ['Tiêu chuẩn', 1, 1, 1], ['Lớn', 1.2, 1.2, 1.25]],
        'wall-lamp' => [['Nhỏ', 0.8, 0.8, 0.8], ['Tiêu chuẩn', 1, 1, 1], ['Lớn', 1.3, 1.25, 1.3]],
        'seat' => [['Nhỏ', 0.9, 0.9, 0.95], ['Tiêu chuẩn', 1, 1, 1], ['Lớn', 1.15, 1.1, 1.05]],
        'default' => [['Nhỏ', 0.8, 0.9, 1], ['Tiêu chuẩn', 1, 1, 1], ['Lớn', 1.25, 1.1, 1]],
    ];

    private const SEATS = ['armchair', 'pouf', 'stool', 'office', 'chair'];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $created = 0;
        $products = 0;

        foreach (Product::query()->with('variants')->orderBy('id')->get() as $product) {
            if ($product->variants->isEmpty() || $this->isBed($product)) {
                continue;
            }

            $distinct = $product->variants->map(fn ($v) => trim((string) $v->size))->unique();
            if ($distinct->count() > 1) {
                continue;
            }

            [$shape] = FurnitureGlb::measure($product->name, $product->dimensions);
            $box = FurnitureGlb::box($product->name, $product->dimensions);
            if ($shape === 'sofa' && str_contains(mb_strtolower($product->name), 'đơn')) {
                $shape = 'armchair';
            }
            $plan = self::PLANS[in_array($shape, self::SEATS, true) ? 'seat' : $shape] ?? self::PLANS['default'];

            $added = $dryRun ? $this->preview($product, $box, $plan) : DB::transaction(fn () => $this->expand($product, $box, $plan));
            $created += $added;
            $products++;
        }

        $this->info(($dryRun ? 'Sẽ thêm ' : 'Đã thêm ')."{$created} biến thể size cho {$products} sản phẩm.");

        return self::SUCCESS;
    }

    private function expand(Product $product, array $box, array $plan): int
    {
        $variants = $product->variants->sortBy('id')->values();
        $sources = $this->finishes($product);
        $created = 0;

        foreach ($plan as $index => [$prefix, $fl, $fw, $fh]) {
            $label = $this->label($prefix, $box, $fl, $fw, $fh);

            if ($fl == 1 && $fw == 1 && $fh == 1) {
                foreach ($variants as $variant) {
                    $variant->update(['size' => $label]);
                }

                continue;
            }

            foreach ($sources as $variant) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'color' => $variant->color,
                    'material' => $variant->material,
                    'size' => $label,
                    'price' => $this->price((float) $variant->price, $fl, $fw),
                    'stock' => max(3, (int) $variant->stock),
                    'sku' => $this->sku($variant->sku, $index),
                ]);
                $created++;
            }
        }

        $this->line("{$product->name}: ".implode(' | ', array_map(fn ($p) => $this->label($p[0], $box, $p[1], $p[2], $p[3]), $plan)));

        return $created;
    }

    private function preview(Product $product, array $box, array $plan): int
    {
        $this->line("{$product->name}: ".implode(' | ', array_map(fn ($p) => $this->label($p[0], $box, $p[1], $p[2], $p[3]), $plan)));

        return (count($plan) - 1) * $this->finishes($product)->count();
    }

    /**
     * One source variant per colour and material, so a new size is not
     * duplicated for every SKU that shares the same finish.
     */
    private function finishes(Product $product)
    {
        return $product->variants
            ->sortBy('id')
            ->unique(fn ($v) => mb_strtolower(trim((string) $v->color)).'|'.mb_strtolower(trim((string) $v->material)))
            ->values();
    }

    private function label(string $prefix, array $box, float $fl, float $fw, float $fh): string
    {
        return sprintf(
            '%s (%s x %s x %s cm)',
            $prefix,
            $this->round($box['l'] * $fl),
            $this->round($box['w'] * $fw),
            $this->round($box['h'] * $fh),
        );
    }

    private function round(float $cm): string
    {
        $value = $cm >= 30 ? round($cm / 5) * 5 : round($cm);

        return (string) (int) max(1, $value);
    }

    private function price(float $price, float $fl, float $fw): float
    {
        $ratio = $fl * $fw;
        $next = $price * (1 + ($ratio - 1) * 0.75);

        return max(round($price * 0.5, -4), round($next, -4));
    }

    private function sku(?string $sku, int $index): ?string
    {
        if (! $sku) {
            return null;
        }

        $candidate = $sku.'-S'.$index;

        return ProductVariant::where('sku', $candidate)->exists() ? null : $candidate;
    }

    private function isBed(Product $product): bool
    {
        $name = mb_strtolower($product->name);

        return str_contains($name, 'giường') && ! str_contains($name, 'kệ') && ! str_contains($name, 'sofa');
    }
}
