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
            ['name' => 'Quản trị Mộc An', 'password' => Hash::make('Admin@123'), 'role' => 'admin'],
        );

        User::updateOrCreate(
            ['email' => 'customer@mocan.test'],
            ['name' => 'Khách hàng Demo', 'password' => Hash::make('Customer@123'), 'role' => 'customer'],
        );

        $this->call([
            ProductCatalogSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
