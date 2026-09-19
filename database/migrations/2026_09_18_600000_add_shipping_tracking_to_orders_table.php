<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_carrier')->nullable()->after('shipping_fee'); // ghn, ghtk, internal
            $table->string('tracking_code')->nullable()->after('shipping_carrier');
            $table->timestamp('shipped_at')->nullable()->after('tracking_code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_carrier', 'tracking_code', 'shipped_at']);
        });
    }
};
