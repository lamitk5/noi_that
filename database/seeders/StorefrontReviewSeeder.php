<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StorefrontReviewSeeder extends Seeder
{
    public function run(): void
    {
        if (Review::query()->exists()) {
            $this->command?->info('Reviews already exist, skipped.');

            return;
        }

        $products = Product::query()->where('is_active', true)->orderBy('id')->get();
        if ($products->isEmpty()) {
            $this->command?->info('No active products, skipped reviews.');

            return;
        }

        $authors = $this->authors();
        $comments = [
            'Sản phẩm đẹp, đóng gói kỹ, giao nhanh.',
            'Chất lượng tốt, đúng mô tả. Sẽ ủng hộ shop tiếp.',
            'Phom dáng tinh giản, hợp căn hộ của mình.',
            'Giao hàng cẩn thận, lắp ráp dễ.',
            'Hoàn thiện tốt, không bị xước hay mối mọt.',
        ];

        foreach ($products as $index => $product) {
            foreach ([5, 4] as $offset => $rating) {
                $author = $authors[($index + $offset) % count($authors)];
                Review::create([
                    'user_id' => $author->id,
                    'product_id' => $product->id,
                    'rating' => $rating,
                    'comment' => $product->name.': '.$comments[($index + $offset) % count($comments)],
                    'is_approved' => true,
                    'created_at' => now()->subDays(2 + $index + $offset),
                    'updated_at' => now()->subDays(2 + $index + $offset),
                ]);
            }
        }

        $this->command?->info('Seeded '.$products->count().' products with reviews.');
    }

    /**
     * @return list<User>
     */
    private function authors(): array
    {
        $existing = User::query()
            ->where('role', 'customer')
            ->where('is_active', true)
            ->orderBy('id')
            ->limit(8)
            ->get();

        if ($existing->isNotEmpty()) {
            return $existing->all();
        }

        $names = ['Nguyễn Minh An', 'Trần Thu Hà', 'Lê Quốc Huy', 'Phạm Ngọc Mai'];
        $authors = [];

        foreach ($names as $i => $name) {
            $email = 'khach-danh-gia-'.($i + 1).'@mocan.invalid';
            $authors[] = User::query()->firstOrCreate(
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
        }

        return $authors;
    }
}
