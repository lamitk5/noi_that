<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasIndex('products', 'products_is_active_category_id_created_at_index')) {
            Schema::table('products', function (Blueprint $table) {
                $table->index(['is_active', 'category_id', 'created_at'], 'products_is_active_category_id_created_at_index');
            });
        }

        if (! Schema::hasIndex('products', 'products_is_active_base_price_index')) {
            Schema::table('products', function (Blueprint $table) {
                $table->index(['is_active', 'base_price'], 'products_is_active_base_price_index');
            });
        }

        if (! Schema::hasIndex('product_variants', 'product_variants_product_id_stock_index')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->index(['product_id', 'stock'], 'product_variants_product_id_stock_index');
            });
        }

        if (! Schema::hasIndex('orders', 'orders_user_id_created_at_index')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index(['user_id', 'created_at'], 'orders_user_id_created_at_index');
            });
        }

        if (! Schema::hasIndex('orders', 'orders_order_status_payment_status_index')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index(['order_status', 'payment_status'], 'orders_order_status_payment_status_index');
            });
        }

        if (! Schema::hasIndex('user_events', 'user_events_event_type_created_at_index')) {
            Schema::table('user_events', function (Blueprint $table) {
                $table->index(['event_type', 'created_at'], 'user_events_event_type_created_at_index');
            });
        }

        if (! Schema::hasIndex('user_events', 'user_events_user_id_created_at_index')) {
            Schema::table('user_events', function (Blueprint $table) {
                $table->index(['user_id', 'created_at'], 'user_events_user_id_created_at_index');
            });
        }

        if (! Schema::hasIndex('reviews', 'reviews_product_id_rating_index')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->index(['product_id', 'rating'], 'reviews_product_id_rating_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasIndex('reviews', 'reviews_product_id_rating_index')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->dropIndex('reviews_product_id_rating_index');
            });
        }

        if (Schema::hasIndex('user_events', 'user_events_event_type_created_at_index')) {
            Schema::table('user_events', function (Blueprint $table) {
                $table->dropIndex('user_events_event_type_created_at_index');
            });
        }

        if (Schema::hasIndex('user_events', 'user_events_user_id_created_at_index')) {
            Schema::table('user_events', function (Blueprint $table) {
                $table->dropIndex('user_events_user_id_created_at_index');
            });
        }

        if (Schema::hasIndex('orders', 'orders_order_status_payment_status_index')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex('orders_order_status_payment_status_index');
            });
        }

        if (Schema::hasIndex('orders', 'orders_user_id_created_at_index')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex('orders_user_id_created_at_index');
            });
        }

        if (Schema::hasIndex('product_variants', 'product_variants_product_id_stock_index')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->dropIndex('product_variants_product_id_stock_index');
            });
        }

        if (Schema::hasIndex('products', 'products_is_active_base_price_index')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex('products_is_active_base_price_index');
            });
        }

        if (Schema::hasIndex('products', 'products_is_active_category_id_created_at_index')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex('products_is_active_category_id_created_at_index');
            });
        }
    }
};
