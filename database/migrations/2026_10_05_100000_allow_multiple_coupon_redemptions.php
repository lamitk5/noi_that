<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupon_redemptions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['order_id']);
            $table->dropUnique(['user_id']);
            $table->dropUnique(['order_id']);
        });

        Schema::table('coupon_redemptions', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->unique(['order_id', 'code']);
        });

        DB::table('coupons')->where('code', 'FREESHIP')->update([
            'name' => 'Miễn phí vận chuyển',
            'value' => 100000000,
            'max_discount_amount' => null,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('coupon_redemptions', function (Blueprint $table) {
            $table->dropUnique(['order_id', 'code']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['order_id']);
            $table->unique('user_id');
            $table->unique('order_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });
    }
};
