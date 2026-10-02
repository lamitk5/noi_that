<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GenerateUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:generate {count=5 : Số lượng tài khoản muốn tạo} {--password=123456 : Mật khẩu chung}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tạo nhanh hàng loạt tài khoản người dùng đã kích hoạt sẵn để kiểm thử';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = (int) $this->argument('count');
        $password = (string) $this->option('password');
        $hashedPassword = Hash::make($password);

        $this->info("Đang tạo {$count} tài khoản thử nghiệm với mật khẩu: '{$password}'...");

        $rows = [];
        for ($i = 1; $i <= $count; $i++) {
            $random = strtolower(Str::random(4));
            $username = "user_{$random}";
            $phone = '09' . random_int(10000000, 99999999);
            $email = "{$username}@example.com";

            // Avoid collisions
            while (User::where('username', $username)->exists()) {
                $random = strtolower(Str::random(4));
                $username = "user_{$random}";
                $email = "{$username}@example.com";
            }

            $user = User::create([
                'username' => $username,
                'name' => 'Khách hàng ' . strtoupper($random),
                'email' => $email,
                'phone' => $phone,
                'password' => $hashedPassword,
                'role' => 'customer',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $rows[] = [$user->id, $user->username, $user->name, $user->email, $user->phone, $password];
        }

        $this->table(['ID', 'Username', 'Họ tên', 'Email', 'SĐT', 'Mật khẩu'], $rows);
        $this->info("Đã tạo thành công {$count} tài khoản! Bạn có thể dùng username, email hoặc SĐT để đăng nhập ngay.");

        return self::SUCCESS;
    }
}
