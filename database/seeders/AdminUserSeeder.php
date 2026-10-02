<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = strtolower(trim((string) config('seeding.admin.email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = 'admin@example.com';
        }

        $user = User::query()->where('email', $email)->first()
            ?? User::query()->whereIn('email', ['admin@example.com', 'admin@furniture.com'])->first();

        if ($user) {
            $user->forceFill([
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
            $this->command?->info('Admin kept: '.$user->email);

            return;
        }

        try {
            User::create([
                'name' => config('seeding.admin.name') ?: 'Shop Admin',
                'username' => $this->uniqueUsername(Str::before($email, '@') ?: 'admin'),
                'email' => $email,
                'password' => Hash::make($this->password()),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $this->command?->info('Admin created: '.$email);
        } catch (Throwable $e) {
            $this->command?->error('Admin seed skipped: '.$e->getMessage());
        }
    }

    private function password(): string
    {
        $raw = (string) (config('seeding.admin.password') ?: 'password123');

        return strlen($raw) >= 8 ? $raw : 'password123';
    }

    private function uniqueUsername(string $base): string
    {
        $base = Str::lower(Str::slug($base, '')) ?: 'admin';
        $username = $base;
        $suffix = 1;

        while (User::query()->where('username', $username)->exists()) {
            $username = $base.'-'.$suffix;
            $suffix++;
        }

        return $username;
    }
}
