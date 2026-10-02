<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class StorefrontReviewSeeder extends Seeder
{
    public function run(): void
    {
        try {
            $this->seedMissing();
        } catch (Throwable $e) {
            $this->command?->error('Review seed skipped: '.$e->getMessage());
        }
    }

    private function seedMissing(): void
    {
        $reviewedIds = DB::table('reviews')->distinct()->pluck('product_id');
        $products = DB::table('products')
            ->where('is_active', 1)
            ->whereNotIn('id', $reviewedIds)
            ->orderBy('id')
            ->get(['id', 'name']);

        if ($products->isEmpty()) {
            $this->command?->info('Every active product already has a review, skipped.');

            return;
        }

        $authorIds = $this->authorIds();
        if ($authorIds === []) {
            $this->command?->error('Review seed skipped: no customer account available.');

            return;
        }

        $comments = [
            'Sản phẩm đẹp, đóng gói kỹ, giao nhanh.',
            'Chất lượng tốt, đúng mô tả. Sẽ ủng hộ shop tiếp.',
            'Phom dáng tinh giản, hợp căn hộ của mình.',
            'Giao hàng cẩn thận, lắp ráp dễ.',
            'Hoàn thiện tốt, không bị xước hay mối mọt.',
        ];

        $rows = [];
        $now = now();
        foreach ($products as $index => $product) {
            foreach ([5, 4] as $offset => $rating) {
                $rows[] = [
                    'user_id' => $authorIds[($index + $offset) % count($authorIds)],
                    'product_id' => $product->id,
                    'order_id' => null,
                    'rating' => $rating,
                    'comment' => $product->name.': '.$comments[($index + $offset) % count($comments)],
                    'is_approved' => true,
                    'created_at' => $now->copy()->subDays(2 + ($index % 20) + $offset),
                    'updated_at' => $now->copy()->subDays(2 + ($index % 20) + $offset),
                ];
            }
        }

        foreach (array_chunk($rows, 40) as $chunk) {
            DB::table('reviews')->insert($chunk);
        }

        $this->command?->info('Seeded '.count($rows).' reviews for '.$products->count().' products.');
    }

    /**
     * @return list<int>
     */
    private function authorIds(): array
    {
        $existing = User::query()
            ->where('role', 'customer')
            ->where('is_active', true)
            ->orderBy('id')
            ->limit(8)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($existing !== []) {
            return $existing;
        }

        $names = ['Nguyễn Minh An', 'Trần Thu Hà', 'Lê Quốc Huy', 'Phạm Ngọc Mai'];
        $ids = [];

        foreach ($names as $i => $name) {
            $email = 'khach-danh-gia-'.($i + 1).'@mocan.invalid';
            $user = User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'username' => 'khach-danh-gia-'.($i + 1),
                    'password' => Hash::make(Str::random(40)),
                    'role' => 'customer',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $ids[] = (int) $user->id;
        }

        return $ids;
    }
}
