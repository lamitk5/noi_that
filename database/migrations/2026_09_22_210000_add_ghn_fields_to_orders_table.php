<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('province_id')->nullable()->after('shipping_address');
            $table->string('province_name')->nullable()->after('province_id');
            $table->unsignedInteger('district_id')->nullable()->after('province_name');
            $table->string('district_name')->nullable()->after('district_id');
            $table->string('ward_code', 30)->nullable()->after('district_name');
            $table->string('ward_name')->nullable()->after('ward_code');

            $table->string('ghn_order_code', 60)->nullable()->index()->after('order_code');
            $table->string('ghn_status', 50)->nullable()->after('ghn_order_code');
            $table->timestamp('ghn_expected_delivery_at')->nullable()->after('ghn_status');
            $table->text('ghn_log')->nullable()->after('ghn_expected_delivery_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'province_id',
                'province_name',
                'district_id',
                'district_name',
                'ward_code',
                'ward_name',
                'ghn_order_code',
                'ghn_status',
                'ghn_expected_delivery_at',
                'ghn_log',
            ]);
        });
    }
};
