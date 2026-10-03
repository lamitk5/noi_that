<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Support\FurnitureGlb;
use Illuminate\Console\Command;

class GenerateProductModelsCommand extends Command
{
    protected $signature = 'products:models {--force : Ghi đè cả file 3D admin đã tải lên}';

    protected $description = 'Tạo mô hình GLB đúng kích thước mét cho sản phẩm chưa có file 3D';

    public function handle(): int
    {
        $dir = storage_path('picture');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $made = 0;
        $skipped = 0;

        foreach (Product::query()->orderBy('id')->get() as $product) {
            $owned = $product->model_glb === null || str_starts_with((string) $product->model_glb, 'picture/mocan-');
            if (! $owned && ! $this->option('force')) {
                $skipped++;
                $this->line("Giữ file sẵn có: {$product->name}");

                continue;
            }

            [$shape, $width, $depth, $height] = FurnitureGlb::measure($product->name, $product->dimensions);
            [$glb, $usdz] = FurnitureGlb::filesFor($product->name, $product->dimensions, $this->photoPath($product));
            $filename = 'mocan-'.$product->id;
            file_put_contents($dir.DIRECTORY_SEPARATOR.$filename.'.glb', $glb);
            file_put_contents($dir.DIRECTORY_SEPARATOR.$filename.'.usdz', $usdz);

            $product->model_glb = 'picture/'.$filename.'.glb';
            $product->model_usdz = 'picture/'.$filename.'.usdz';
            $product->save();
            $made++;
            $this->line(sprintf(
                '%s → %s %.0f × %.0f × %.0f cm (GLB %s KB, USDZ %s KB)',
                $product->name,
                $shape,
                $width,
                $depth,
                $height,
                number_format(strlen($glb) / 1024, 1),
                number_format(strlen($usdz) / 1024, 1)
            ));
        }

        $this->info("Đã tạo {$made} cặp GLB/USDZ. Bỏ qua {$skipped} sản phẩm đã có mô hình riêng.");

        return self::SUCCESS;
    }

    private function photoPath(Product $product): ?string
    {
        $product->loadMissing('primaryImage');
        $path = $product->primaryImage?->image_path;
        if (! is_string($path) || ! str_starts_with($path, 'picture/')) {
            return null;
        }

        $file = storage_path('picture/'.basename($path));

        return is_file($file) ? $file : null;
    }
}
