<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_executes_successfully_and_populates_demo_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@mocan.test',
            'role' => 'admin',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'customer@mocan.test',
            'role' => 'customer',
        ]);

        $this->assertDatabaseHas('vouchers', [
            'code' => 'MOCAN10',
        ]);

        $this->assertDatabaseHas('faqs', [
            'category' => 'delivery',
        ]);

        $this->assertDatabaseHas('cms_pages', [
            'slug' => 'gioi-thieu',
        ]);

        $this->assertDatabaseHas('posts', [
            'slug' => '5-bi-quyet-bo-tri-phong-khach-am-cung',
        ]);
    }
}
