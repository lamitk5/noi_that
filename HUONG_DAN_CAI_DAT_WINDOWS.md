# Hướng dẫn chạy dự án Mộc An trên Windows

## 1. Phần mềm cần cài

- Visual Studio Code
- XAMPP có PHP 8.2 trở lên
- Composer
- Node.js bản LTS

Kiểm tra trong Command Prompt hoặc Terminal của VS Code:

```bash
php -v
composer -V
node -v
npm -v
```

## 2. Mở source bằng VS Code

1. Giải nén file ZIP.
2. Mở Visual Studio Code.
3. Chọn **File > Open Folder**.
4. Chọn đúng thư mục `noi_that` vừa giải nén.
5. Trong VS Code, chọn **Terminal > New Terminal**.

Terminal phải hiển thị đường dẫn kết thúc bằng `noi_that`.

## 3. Cài thư viện

Chạy lần lượt từng lệnh:

```bash
composer install
npm install
```

## 4. Tạo file cấu hình Laravel

Với PowerShell:

```powershell
Copy-Item .env.example .env
```

Với Command Prompt:

```cmd
copy .env.example .env
```

Sau đó chạy:

```bash
php artisan key:generate
```

## 5. Tạo database

1. Mở XAMPP Control Panel.
2. Bật **Apache** và **MySQL**.
3. Truy cập `http://localhost/phpmyadmin`.
4. Chọn **New** và tạo database tên `noi_that_db` với collation `utf8mb4_unicode_ci`.
5. Mở file `.env` và sửa:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=noi_that_db
DB_USERNAME=root
DB_PASSWORD=
```

## 6. Tạo bảng và dữ liệu mẫu

Chỉ dùng `migrate:fresh` khi database mới hoặc không có dữ liệu cần giữ:

```bash
php artisan migrate:fresh --seed
```

## 7. Chạy website

Mở hai Terminal trong VS Code.

Terminal 1:

```bash
npm run dev
```

Terminal 2:

```bash
php artisan serve
```

Mở địa chỉ Laravel hiển thị trong Terminal, thông thường là:

`http://127.0.0.1:8000`

## Tài khoản dữ liệu mẫu

Admin:

- Email: `admin@mocan.test`
- Mật khẩu: `Admin@123`

Khách hàng:

- Email: `customer@mocan.test`
- Mật khẩu: `Customer@123`

## Lỗi thường gặp

- `php is not recognized`: PHP chưa được thêm vào PATH hoặc Terminal chưa mở lại sau khi cấu hình.
- `composer is not recognized`: Composer chưa được cài hoặc chưa khởi động lại VS Code.
- `Unknown database noi_that_db`: Chưa tạo database trong phpMyAdmin hoặc tên trong `.env` bị sai.
- `Vite manifest not found`: Chưa chạy `npm install` và `npm run dev`.
- Không kết nối được MySQL: Kiểm tra MySQL trong XAMPP đã bật hay chưa.
