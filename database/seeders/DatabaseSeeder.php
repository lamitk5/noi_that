<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@mocan.test'],
            [
                'name' => 'Quản trị Mộc An',
                'phone' => '0901234567',
                'password' => Hash::make('Admin@123'),
                'role' => 'admin',
            ],
        );

        User::updateOrCreate(
            ['email' => 'customer@mocan.test'],
            [
                'name' => 'Khách hàng Demo',
                'phone' => '0909876543',
                'password' => Hash::make('Customer@123'),
                'role' => 'customer',
            ],
        );

        // PictureProductSeeder wipes products then inserts 1 product per image file
        $this->call(PictureProductSeeder::class);

        \App\Models\Coupon::updateOrCreate(
            ['code' => 'MOCAN10'],
            [
                'name' => 'Ưu đãi thành viên Mộc An - Giảm 10%',
                'type' => 'percent',
                'value' => 10,
                'min_order_amount' => 1000000,
                'max_discount_amount' => 500000,
                'usage_limit' => 100,
                'is_active' => true,
            ]
        );

        \App\Models\Coupon::updateOrCreate(
            ['code' => 'GIAM50K'],
            [
                'name' => 'Voucher chào bạn mới - Giảm 50K',
                'type' => 'fixed',
                'value' => 50000,
                'min_order_amount' => 200000,
                'usage_limit' => 200,
                'is_active' => true,
            ]
        );

        // Seed demo orders for Analytics & Reporting if empty
        if (\App\Models\Order::count() === 0) {
            $products = \App\Models\Product::with('variants')->take(4)->get();
            if ($products->isNotEmpty()) {
                $customer = \App\Models\User::where('role', 'customer')->first();
                $demoOrders = [
                    [
                        'order_code' => 'ORD-MOCAN-001',
                        'customer_name' => 'Nguyễn Thị Lan',
                        'customer_phone' => '0912345678',
                        'customer_email' => 'lan.nguyen@example.com',
                        'shipping_address' => '25 Hoàng Đạo Thúy, Cầu Giấy, Hà Nội',
                        'total_price' => 5490000,
                        'shipping_fee' => 0,
                        'payment_method' => 'cod',
                        'payment_status' => 'paid',
                        'order_status' => 'completed',
                        'created_at' => now()->subHours(2),
                    ],
                    [
                        'order_code' => 'ORD-MOCAN-002',
                        'customer_name' => 'Trần Văn Minh',
                        'customer_phone' => '0987654321',
                        'customer_email' => 'minh.tran@example.com',
                        'shipping_address' => '120 Lê Lợi, Quận 1, TP. Hồ Chí Minh',
                        'total_price' => 8990000,
                        'shipping_fee' => 0,
                        'payment_method' => 'vnpay',
                        'payment_status' => 'pending',
                        'order_status' => 'pending',
                        'created_at' => now()->subHours(1),
                    ],
                    [
                        'order_code' => 'ORD-MOCAN-003',
                        'customer_name' => 'Phạm Hoàng Nam',
                        'customer_phone' => '0933445566',
                        'customer_email' => 'nam.pham@example.com',
                        'shipping_address' => '45 Nguyễn Thị Minh Khai, Đà Nẵng',
                        'total_price' => 4590000,
                        'shipping_fee' => 0,
                        'payment_method' => 'cod',
                        'payment_status' => 'pending',
                        'order_status' => 'shipping',
                        'created_at' => now()->subDays(1),
                    ],
                    [
                        'order_code' => 'ORD-MOCAN-004',
                        'customer_name' => 'Lê Thanh Thảo',
                        'customer_phone' => '0977889900',
                        'customer_email' => 'thao.le@example.com',
                        'shipping_address' => '88 Nguyễn Du, Hai Bà Trưng, Hà Nội',
                        'total_price' => 12500000,
                        'shipping_fee' => 0,
                        'payment_method' => 'momo',
                        'payment_status' => 'paid',
                        'order_status' => 'completed',
                        'created_at' => now()->subDays(2),
                    ],
                    [
                        'order_code' => 'ORD-MOCAN-005',
                        'customer_name' => 'Hoàng Đức Anh',
                        'customer_phone' => '0966112233',
                        'customer_email' => 'ducanh@example.com',
                        'shipping_address' => '12 Thảo Điền, TP. Thủ Đức',
                        'total_price' => 3200000,
                        'shipping_fee' => 0,
                        'payment_method' => 'cod',
                        'payment_status' => 'failed',
                        'order_status' => 'canceled',
                        'created_at' => now()->subDays(4),
                    ],
                ];

                foreach ($demoOrders as $idx => $orderData) {
                    $order = \App\Models\Order::create(array_merge($orderData, [
                        'user_id' => $customer?->id,
                    ]));
                    $p = $products[$idx % $products->count()];
                    $variant = $p->variants->first();
                    $order->items()->create([
                        'product_variant_id' => $variant?->id,
                        'product_name' => $p->name,
                        'variant_info' => $variant?->color ?? 'Tiêu chuẩn',
                        'quantity' => 1,
                        'price' => $order->total_price,
                    ]);
                }
            }
        }
    }
}
