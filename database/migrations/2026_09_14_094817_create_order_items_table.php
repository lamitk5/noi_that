<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('product_name')->comment('Tên sản phẩm tại thời điểm mua');
            $table->string('variant_info')->comment('Màu sắc, kích thước và chất liệu tại thời điểm mua');
            $table->unsignedInteger('quantity');
            $table->decimal('price', 15, 2)->comment('Đơn giá tại thời điểm mua');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
