# Đề tài: Thiết Kế Website Thương Mại Điện Tử Bán Đồ Nội Thất (Furniture Shop)

Hệ thống backend hoàn chỉnh cho website thương mại điện tử chuyên kinh doanh các sản phẩm nội thất cao cấp (phòng khách, phòng ngủ, phòng ăn, phòng làm việc, đồ trang trí).

---

## 1. Công nghệ & Kiến trúc Hệ thống

- **Framework**: Laravel 12 (PHP 8.2+)
- **Database**: SQLite / MySQL (Hỗ trợ chuyển đổi linh hoạt qua `.env`)
- **Kiến trúc**: MVC (Model - View - Controller), Clean Architecture
- **Xử lý giao dịch**: Database Transactions đảm bảo tính toàn vẹn dữ liệu khi đặt hàng và trừ tồn kho
- **Giỏ hàng**: Session-based Shopping Cart hỗ trợ cả khách vãng lai và thành viên
- **Phân quyền**: Role-based Access Control (`admin`, `customer`) với Custom Middleware

---

## 2. Cấu trúc Cơ sở Dữ liệu (Database Schema)

Hệ thống bao gồm 6 bảng chính với các ràng buộc toàn vẹn dữ liệu (Foreign Keys & Cascade Delete):

1. **`users`**: Quản lý tài khoản khách hàng và quản trị viên
   - `id`, `name`, `email`, `password`, `role` (`admin` | `customer`), `phone`, `address`, `avatar`, `timestamps`
2. **`categories`**: Danh mục nội thất đa cấp
   - `id`, `name`, `slug`, `description`, `image`, `is_active`, `parent_id`, `timestamps`
3. **`products`**: Sản phẩm nội thất với thông số kỹ thuật đặc thù
   - `id`, `category_id`, `name`, `slug`, `sku`, `short_description`, `description`, `price`, `sale_price`, `stock_quantity`, `material` (gỗ sồi, gỗ óc chó, da bò, nỉ...), `dimensions` (dài x rộng x cao), `color`, `is_featured`, `is_active`, `views_count`, `timestamps`
4. **`product_images`**: Thư viện ảnh chi tiết cho từng sản phẩm
   - `id`, `product_id`, `image_path`, `is_primary`, `sort_order`, `timestamps`
5. **`orders`**: Quản lý đơn đặt hàng
   - `id`, `order_number` (Mã đơn hàng duy nhất), `user_id` (nullable cho khách mua nhanh), `customer_name`, `customer_email`, `customer_phone`, `shipping_address`, `subtotal`, `shipping_fee`, `total_amount`, `payment_method` (`cod` | `banking`), `payment_status` (`pending` | `paid` | `failed`), `order_status` (`pending` | `confirmed` | `shipping` | `completed` | `cancelled`), `notes`, `timestamps`
6. **`order_items`**: Chi tiết sản phẩm trong từng đơn hàng
   - `id`, `order_id`, `product_id`, `product_name`, `price`, `quantity`, `total`, `timestamps`

---

## 3. Các tính năng Backend chính

### Phân hệ Khách hàng (Client Portal)
- **Trang chủ (`/`)**: Sản phẩm nổi bật, sản phẩm mới, danh mục bán chạy, banner khuyến mãi.
- **Danh mục & Tìm kiếm (`/products`)**:
  - Lọc theo danh mục, khoảng giá (min - max), chất liệu (gỗ, da, nỉ...), màu sắc.
  - Sắp xếp: Giá tăng dần, giá giảm dần, mới nhất, phổ biến nhất.
  - Tìm kiếm toàn văn theo tên và mô tả sản phẩm.
- **Chi tiết sản phẩm (`/products/{slug}`)**: Bộ sưu tập nhiều ảnh, thông số kích thước/chất liệu, tình trạng kho hàng, sản phẩm tương tự cùng danh mục.
- **Giỏ hàng (`/cart`)**: Thêm sản phẩm, cập nhật số lượng (ràng buộc tồn kho thực tế), xóa từng món hoặc dọn sạch giỏ hàng.
- **Đặt hàng (`/checkout`)**: Điền thông tin giao hàng, chọn hình thức thanh toán COD hoặc Chuyển khoản, tự động trừ số lượng tồn kho qua DB Transaction.
- **Tra cứu đơn hàng (`/orders/track`)**: Tra cứu tiến độ đơn hàng nhanh theo mã vận đơn và số điện thoại.
- **Tài khoản cá nhân (`/login`, `/register`, `/logout`)**: Đăng nhập, đăng ký và theo dõi lịch sử đơn hàng.

### Phân hệ Quản trị viên (Admin Portal - `/admin`)
- **Bảng điều khiển (Dashboard)**: Thống kê tổng doanh thu, số lượng đơn hàng, số sản phẩm, số khách hàng, biểu đồ đơn hàng gần đây và cảnh báo hàng sắp hết kho.
- **Quản lý danh mục (`/admin/categories`)**: Thêm, sửa, xóa, kích hoạt/ẩn danh mục nội thất.
- **Quản lý sản phẩm (`/admin/products`)**: Quản lý sản phẩm, thông số nội thất, upload nhiều ảnh và chọn ảnh đại diện.
- **Quản lý đơn hàng (`/admin/orders`)**: Lọc theo trạng thái đơn hàng, xem chi tiết, cập nhật tiến trình giao hàng (`pending` -> `confirmed` -> `shipping` -> `completed`).

---

## 4. Hướng dẫn Cài đặt & Vận hành

### Yêu cầu môi trường
- PHP >= 8.2 (kèm extension: `pdo`, `sqlite3` hoặc `mysqli`, `mbstring`, `openssl`)
- Composer >= 2.x

### Các bước chạy dự án
```bash
# 1. Cài đặt các thư viện phụ thuộc
composer install

# 2. Cấu hình file môi trường
cp .env.example .env
php artisan key:generate

# 3. Chạy migration và nạp dữ liệu mẫu
php artisan migrate:fresh --seed

# 4. Khởi chạy máy chủ phát triển
php artisan serve
```

Truy cập website tại: `http://127.0.0.1:8000`

---

## 5. Tài khoản Truy cập Mẫu

- **Tài khoản Quản trị (Admin)**:
  - Email: `admin@furniture.com`
  - Mật khẩu: `password123`
- **Tài khoản Khách hàng (Customer)**:
  - Email: `customer@example.com`
  - Mật khẩu: `password123`
