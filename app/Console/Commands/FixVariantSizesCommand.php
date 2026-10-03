<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Support\FurnitureGlb;
use Illuminate\Console\Command;

class FixVariantSizesCommand extends Command
{
    protected $signature = 'products:fix-sizes';

    protected $description = 'Đưa kích thước biến thể về đúng số đo sản phẩm, giữ các size thật của bàn ăn, bàn làm việc và giường';

    public function handle(): int
    {
        $updated = 0;

        foreach (Product::query()->with('variants')->orderBy('id')->get() as $product) {
            if ($this->isBed($product)) {
                $updated += $this->fixBed($product);

                continue;
            }

            if ($product->variants->map(fn ($v) => trim((string) $v->size))->unique()->count() > 1) {
                continue;
            }

            foreach ($product->variants as $variant) {
                $next = $this->sizeFor($product, (string) $variant->size);
                if ($next === null || $next === $variant->size) {
                    continue;
                }

                $variant->size = $next;
                $variant->save();
                $updated++;
                $this->line("{$product->name}: {$next}");
            }
        }

        $this->info("Đã sửa {$updated} biến thể.");

        return self::SUCCESS;
    }

    private function isBed(Product $product): bool
    {
        $name = mb_strtolower($product->name);

        return str_contains($name, 'giường')
            && ! str_contains($name, 'kệ')
            && ! str_contains($name, 'sofa');
    }

    private function fixBed(Product $product): int
    {
        $cycle = ['160 x 200 x 45 cm', '180 x 200 x 45 cm', '200 x 220 x 45 cm'];
        $updated = 0;

        foreach ($product->variants->sortBy('id')->values() as $index => $variant) {
            $label = mb_strtolower((string) $variant->size);
            $next = match (true) {
                str_contains($label, 'super') || str_contains($label, '2m2') => $cycle[2],
                str_contains($label, '1m8') || str_contains($label, 'king') => $cycle[1],
                str_contains($label, '1m6') || str_contains($label, 'queen') => $cycle[0],
                in_array($variant->size, $cycle, true) => null,
                default => $cycle[$index % 3],
            };
            if ($next === null || $next === $variant->size) {
                continue;
            }

            $variant->size = $next;
            $variant->save();
            $updated++;
            $this->line("{$product->name}: {$next}");
        }

        return $updated;
    }

    private function sizeFor(Product $product, string $current): ?string
    {
        $name = mb_strtolower($product->name);

        if (str_contains($name, 'bàn ăn') || str_contains($name, 'bàn làm việc')) {
            return null;
        }

        return $this->catalogSize($product);
    }

    private function catalogSize(Product $product): string
    {
        return FurnitureGlb::boxLabel($product->name, $product->dimensions);
    }
}
