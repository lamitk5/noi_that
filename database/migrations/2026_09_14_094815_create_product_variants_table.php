<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('color')->nullable()->comment('Màu sắc');
            $table->string('size')->nullable()->comment('Kích thước');
            $table->string('material')->nullable()->comment('Chất liệu');
            $table->decimal('price', 15, 2)->comment('Giá riêng của biến thể');
            $table->unsignedInteger('stock')->default(0)->comment('Số lượng tồn kho');
            $table->string('sku')->unique()->comment('Mã SKU riêng của biến thể');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
