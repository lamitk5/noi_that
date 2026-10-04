<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * A few reviews written against delivered orders of real accounts.
     * Skipped when that order or product is not in this database.
     */
    private const REVIEWS = [
        [
            'order_code' => 'ORD-MOCAN-001',
            'slug' => 'bon-tam',
            'rating' => 5,
            'days_ago' => 18,
            'comment' => 'Bồn tắm đặt vừa phòng, men láng lau một lần là sạch. Bên giao bê cẩn thận, mép không mẻ.',
        ],
        [
            'order_code' => 'ORD-20261002-SGXUZX',
            'slug' => 'dao-bep',
            'rating' => 5,
            'days_ago' => 6,
            'comment' => 'Đảo bếp chắc chân, mặt bàn dễ lau. Khoảng để chân phía dưới đủ kê hai ghế mà vẫn đi lại được.',
        ],
        [
            'order_code' => 'ORD-20260927-JYPDQ6',
            'slug' => 'ghe-don',
            'rating' => 4,
            'days_ago' => 11,
            'comment' => 'Ghế đôn gọn, kê cuối sofa vừa khít. Mặt ngồi hơi cứng nhưng gỗ đều màu, không lệch vân.',
        ],
    ];

    public function up(): void
    {
        foreach (self::REVIEWS as $plan) {
            $order = DB::table('orders')
                ->where('order_code', $plan['order_code'])
                ->where('order_status', 'completed')
                ->whereNotNull('user_id')
                ->first();

            if (! $order) {
                continue;
            }

            $productId = DB::table('order_items')
                ->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
                ->join('products', 'products.id', '=', 'product_variants.product_id')
                ->where('order_items.order_id', $order->id)
                ->where('products.slug', $plan['slug'])
                ->value('products.id');

            if (! $productId) {
                continue;
            }

            $writtenAt = now()->subDays($plan['days_ago']);
            $existing = DB::table('reviews')
                ->where('user_id', $order->user_id)
                ->where('product_id', $productId)
                ->first();

            $payload = [
                'order_id' => $order->id,
                'rating' => $plan['rating'],
                'comment' => $plan['comment'],
                'is_approved' => true,
                'updated_at' => $writtenAt,
            ];

            if ($existing) {
                DB::table('reviews')->where('id', $existing->id)->update($payload);
            } else {
                DB::table('reviews')->insert($payload + [
                    'user_id' => $order->user_id,
                    'product_id' => $productId,
                    'created_at' => $writtenAt,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('reviews')
            ->whereIn('comment', array_column(self::REVIEWS, 'comment'))
            ->delete();
    }
};
