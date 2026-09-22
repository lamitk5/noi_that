<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_status')->default('pending')->index();
            $table->string('ghn_order_code')->nullable()->index();
            $table->unsignedInteger('ghn_total_fee')->default(0);
            $table->unsignedInteger('to_district_id')->nullable();
            $table->string('to_ward_code', 20)->nullable();
            $table->timestamp('shipping_last_synced_at')->nullable();
        });

        Schema::table('user_addresses', function (Blueprint $table) {
            $table->unsignedInteger('province_id')->nullable();
            $table->unsignedInteger('district_id')->nullable();
            $table->string('ward_code', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_status',
                'ghn_order_code',
                'ghn_total_fee',
                'to_district_id',
                'to_ward_code',
                'shipping_last_synced_at',
            ]);
        });

        Schema::table('user_addresses', function (Blueprint $table) {
            $table->dropColumn([
                'province_id',
                'district_id',
                'ward_code',
            ]);
        });
    }
};
