<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('order_code')->unique();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email');
            $table->text('shipping_address');
            $table->text('note')->nullable();
            $table->decimal('total_price', 15, 2);
            $table->decimal('shipping_fee', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->string('coupon_code')->nullable();
            $table->string('payment_method')->default('cod');
            $table->string('payment_status')->default('pending');
            $table->string('order_status')->default('pending');
            $table->unsignedInteger('province_id')->nullable();
            $table->string('province_name')->nullable();
            $table->unsignedInteger('district_id')->nullable();
            $table->string('district_name')->nullable();
            $table->string('ward_code')->nullable();
            $table->string('ward_name')->nullable();
            $table->string('ghn_order_code', 60)->nullable()->index();
            $table->string('ghn_status', 50)->nullable();
            $table->timestamp('ghn_expected_delivery_at')->nullable();
            $table->text('ghn_log')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
