<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add missing columns to orders table if not exist
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'name')) {
                $table->string('name')->nullable()->after('order_code');
            }
            if (!Schema::hasColumn('orders', 'phone')) {
                $table->string('phone', 50)->nullable()->after('name');
            }
            if (!Schema::hasColumn('orders', 'status')) {
                $table->string('status', 50)->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('orders', 'shipping_status')) {
                $table->string('shipping_status', 50)->nullable()->after('status');
            }
        });

        // 2. Add missing columns to payment_transactions table if not exist
        Schema::table('payment_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_transactions', 'gateway')) {
                $table->string('gateway', 50)->nullable()->after('order_id');
            }
            if (!Schema::hasColumn('payment_transactions', 'message')) {
                $table->text('message')->nullable()->after('status');
            }
        });

        // Make provider and provider_reference nullable for COD transactions if MySQL
        try {
            DB::statement('ALTER TABLE payment_transactions MODIFY provider VARCHAR(30) NULL');
            DB::statement('ALTER TABLE payment_transactions MODIFY provider_reference VARCHAR(100) NULL');
        } catch (\Throwable $e) {
            // Ignore if already nullable or driver not supporting ALTER MODIFY
        }

        // 3. Backfill data for orders
        try {
            if (Schema::hasColumn('orders', 'name') && Schema::hasColumn('orders', 'customer_name')) {
                DB::table('orders')->whereNull('name')->update([
                    'name' => DB::raw('customer_name')
                ]);
            }
            if (Schema::hasColumn('orders', 'phone') && Schema::hasColumn('orders', 'customer_phone')) {
                DB::table('orders')->whereNull('phone')->update([
                    'phone' => DB::raw('customer_phone')
                ]);
            }
            if (Schema::hasColumn('orders', 'status')) {
                DB::table('orders')->whereNull('status')->update([
                    'status' => DB::raw("CASE 
                        WHEN payment_method = 'cod' AND payment_status = 'paid' THEN 'cod_paid'
                        WHEN payment_method = 'cod' THEN 'cod_ordered'
                        WHEN payment_method = 'momo' AND payment_status = 'paid' THEN 'paid_momo'
                        ELSE order_status 
                    END")
                ]);
            }
            if (Schema::hasColumn('orders', 'shipping_status')) {
                DB::table('orders')->whereNull('shipping_status')->update([
                    'shipping_status' => DB::raw("COALESCE(ghn_status, 'pending')")
                ]);
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        // 4. Backfill data for payment_transactions
        try {
            if (Schema::hasColumn('payment_transactions', 'gateway')) {
                DB::table('payment_transactions')->whereNull('gateway')->update([
                    'gateway' => DB::raw("COALESCE(provider, 'cod')")
                ]);
            }
        } catch (\Throwable $e) {
            // Ignore
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $dropCols = [];
            foreach (['name', 'phone', 'status', 'shipping_status'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $dropCols[] = $col;
                }
            }
            if (!empty($dropCols)) {
                $table->dropColumn($dropCols);
            }
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            $dropCols = [];
            foreach (['gateway', 'message'] as $col) {
                if (Schema::hasColumn('payment_transactions', $col)) {
                    $dropCols[] = $col;
                }
            }
            if (!empty($dropCols)) {
                $table->dropColumn($dropCols);
            }
        });
    }
};
