<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $configuredEmail = trim((string) config('seeding.admin.email'));
        $emails = array_values(array_unique(array_filter([
            filter_var($configuredEmail, FILTER_VALIDATE_EMAIL) ? $configuredEmail : null,
            'admin@example.com',
            'admin@furniture.com',
        ])));

        $rawPassword = (string) (config('seeding.admin.password') ?: 'password123');
        if (strlen($rawPassword) < 8) {
            $rawPassword = 'password123';
        }

        foreach ($emails as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->forceFill([
                    'role' => 'admin',
                    'password' => Hash::make($rawPassword),
                    'is_active' => true,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();
                $this->command?->info("Admin updated: {$email}");
            } else {
                User::create([
                    'name' => config('seeding.admin.name') ?: 'Shop Admin',
                    'username' => explode('@', $email)[0],
                    'email' => $email,
                    'password' => Hash::make($rawPassword),
                    'role' => 'admin',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);
                $this->command?->info("Admin created: {$email}");
            }
        }
    }
}
