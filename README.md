# NỘI THẤT MỘC AN — E-COMMERCE PLATFORM

> Nền tảng Thương mại Điện tử Nội thất Cao cấp xây dựng trên nền tảng **Laravel 11**, **Vite**, **Tailwind CSS** và **Alpine.js**.

---

## 🌟 ĐẶC ĐIỂM NỔI BẬT

- **Kiến trúc Hiện đại:** Laravel 11.x, tuân thủ nguyên tắc SOLID, Clean Architecture và MVC chuẩn mực.
- **Trải nghiệm Đa giao diện (Multi-theme System):** 5 phong cách thiết kế độc đáo: *Mộc An (Mặc định)*, *Vintage Cổ Điển*, *Modern Hiện Đại*, *Minimalist Tối Giản*, *Luxury Sang Trọng*.
- **Quản lý Sản phẩm & Biến thể Đa cấp:** Hỗ trợ sản phẩm đơn và biến thể theo Kích thước, Màu sắc, Chất liệu với giá và tồn kho riêng biệt theo SKU.
- **Quản trị Tồn kho Nguyên tử (Atomic Inventory):** Khóa giao dịch DB Transaction chống oversell khi có nhiều người cùng đặt hàng.
- **Thanh toán Đa dạng:** Tiền mặt (COD), Chuyển khoản ngân hàng, Tích hợp cổng thanh toán trực tuyến VNPAY Sandbox & MoMo Sandbox.
- **Vòng đời Đơn hàng Toàn diện:** Quy trình khép kín: *Chờ xác nhận → Đã xác nhận → Đang đóng gói → Đang giao hàng (Tự động cấp mã vận đơn GHN/GHTK) → Hoàn thành*. Hỗ trợ hủy đơn và hoàn kho tự động.
- **Chăm sóc Khách hàng & Loyalty:** Hệ thống tích điểm thành viên (Loyalty Tiers: Đồng, Bạc, Vàng, Kim Cương), Mã khuyến mãi Voucher, Đánh giá sản phẩm đã mua (Verified Reviews), Hệ thống Ticket hỗ trợ & Hỏi đáp FAQ.
- **Quản trị Toàn năng (Admin Panel):** Dashboard trực quan, Báo cáo doanh thu thời gian thực, Phân tích giỏ hàng bị bỏ quên (Cart Abandonment), Quản lý Kho, Đơn hàng, CMS Tin tức và Khách hàng.

---

## 🚀 HƯỚNG DẪN CÀI ĐẶT & CHẠY ỨNG DỤNG

### 1. Yêu cầu hệ thống
- **PHP** >= 8.2 (extensions: `pdo_sqlite` hoặc `pdo_mysql`, `mbstring`, `openssl`, `curl`)
- **Composer** >= 2.0
- **Node.js** >= 20.x & **npm** >= 10.x

### 2. Cài đặt các gói phụ thuộc

```bash
composer install
npm install
```

### 3. Cấu hình Môi trường & Cơ sở dữ liệu

```bash
cp .env.example .env
php artisan key:generate
```

Cấu hình kết nối DB trong file `.env` (mặc định hỗ trợ sẵn SQLite):
```env
DB_CONNECTION=sqlite
```

### 4. Nạp cơ sở dữ liệu mẫu (Seed Data)

```bash
php artisan migrate --seed
```

### 5. Build Assets và Khởi động Server

```bash
# Build assets giao diện
npm run build

# Chạy server ứng dụng
php artisan serve
```

Truy cập hệ thống tại: `http://127.0.0.1:8000`

---

## 🔑 TÀI KHOẢN TRẢI NGHIỆM (DEMO ACCOUNTS)

| Vai trò | Email đăng nhập | Mật khẩu | Chức năng chính |
| :--- | :--- | :--- | :--- |
| **Quản trị viên (Admin)** | `admin@mocan.test` | `Admin@123` | Toàn quyền Quản trị, Kho hàng, Đơn hàng, Doanh thu, CMS |
| **Khách hàng thân thiết (VIP)** | `customer@mocan.test` | `Customer@123` | Mua hàng, Giỏ hàng, Áp dụng Voucher, Đánh giá sản phẩm |

---

## 📖 TÀI LIỆU HƯỚNG DẪN DEMO CHI TIẾT

Chi tiết từng bước kịch bản demo Khách hàng và Quản trị viên được trình bày đầy đủ tại:
👉 **[Tài liệu Hướng dẫn Demo & Nghiệm thu (DEMO_GUIDE.md)](docs/DEMO_GUIDE.md)**

---

## 🧪 KIỂM THỬ TỰ ĐỘNG (AUTOMATED TESTING)

Hệ thống đạt tiêu chuẩn kiểm thử khắt khe với 100% tests vượt qua:

```bash
php artisan test
```

- **Kết quả:** `268 PASS / 0 FAIL / 992 assertions`
- **Độ bao phủ:** Unit Test & Feature Test bao phủ trọn vẹn Luồng Giỏ hàng, Đặt hàng, Khóa tồn kho, Trừ điểm, Cổng thanh toán Sandbox, Vòng đời Đơn hàng và Phân quyền Admin.

---

## 📄 BẢN QUYỀN

Dự án được xây dựng và phát triển cho môn học PTHT Thương mại Điện tử. Mọi quyền được bảo lưu.
