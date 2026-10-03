<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class GenerateProductModelsCommand extends Command
{
    protected $signature = 'products:clear-models';

    protected $description = 'Gỡ các file 3D tự tạo (mocan-*.glb/.usdz) khỏi sản phẩm';

    public function handle(): int
    {
        $cleared = 0;

        foreach (Product::query()->where('model_glb', 'like', 'picture/mocan-%')->orWhere('model_usdz', 'like', 'picture/mocan-%')->get() as $product) {
            foreach ([$product->model_glb, $product->model_usdz] as $path) {
                if (is_string($path) && str_starts_with($path, 'picture/mocan-')) {
                    $file = storage_path('picture/'.basename($path));
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }

            $product->model_glb = null;
            $product->model_usdz = null;
            $product->save();
            $cleared++;
        }

        foreach (glob(storage_path('picture/mocan-*.{glb,usdz}'), GLOB_BRACE) ?: [] as $file) {
            unlink($file);
        }

        $this->info("Đã gỡ mô hình 3D tự tạo khỏi {$cleared} sản phẩm.");

        return self::SUCCESS;
    }
}
