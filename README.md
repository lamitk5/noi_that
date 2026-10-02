# Đề tài: Thiết Kế Website Thương Mại Điện Tử Bán Đồ Nội Thất (Furniture Shop - Mộc An)

Hệ thống thương mại điện tử hoàn chỉnh cho thương hiệu nội thất cao cấp Mộc An, cung cấp trải nghiệm mua sắm hiện đại, tích hợp thanh toán trực tuyến, vận chuyển tự động và live chat chăm sóc khách hàng.

---

## 1. Công nghệ & Kiến trúc Hệ thống

- **Framework**: Laravel 12 (PHP 8.2+)
- **Frontend**: Blade Templates, Tailwind CSS v4, Alpine.js, Vite
- **Database**: SQLite / MySQL (Hỗ trợ chuyển đổi linh hoạt qua file `.env`)
- **Kiến trúc**: MVC (Model - View - Controller), Clean Architecture
- **Xử lý giao dịch**: Database Transactions đảm bảo tính toàn vẹn dữ liệu khi đặt hàng và trừ kho
- **Thanh toán**: Tích hợp Cổng thanh toán **VNPAY-QR** và Ví điện tử **MoMo ATM**
- **Vận chuyển**: Tích hợp API **Giao Hàng Nhanh (GHN)** tính phí theo địa chỉ và tra cứu vận đơn
- **Hỗ trợ khách hàng**: Hệ thống Live Chat Realtime 1-1 giữa khách hàng và nhân viên tư vấn
- **Thông báo**: Hệ thống Toast Notification toàn cục tự động bắt sự kiện thao tác
- **Phân quyền**: Role-based Access Control (`admin`, `staff`, `customer`) với Custom Middleware

---

## 2. Cấu trúc Thư mục Dự án (Project Structure)

```
furniture-shop/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/                      # Quản trị viên & nhân viên nội bộ
│   │   │   │   ├── AnalyticsController.php # Báo cáo, phân tích doanh thu & xuất Excel/PDF
│   │   │   │   ├── CategoryController.php  # Quản lý danh mục nội thất
│   │   │   │   ├── ChatController.php      # Tiếp nhận & phản hồi tin nhắn khách hàng
│   │   │   │   ├── CouponController.php    # Quản lý mã giảm giá / khuyến mãi
│   │   │   │   ├── CustomerController.php  # Quản lý hồ sơ khách hàng
│   │   │   │   ├── FinanceController.php   # Quản lý tài chính & lịch sử giao dịch
│   │   │   │   ├── OrderController.php     # Xử lý đơn hàng & xuất vận đơn GHN
│   │   │   │   ├── ProductController.php   # Quản lý sản phẩm, biến thể, hình ảnh
│   │   │   │   ├── ReviewController.php    # Kiểm duyệt đánh giá sản phẩm
│   │   │   │   └── StaffController.php     # Quản lý tài khoản nhân viên & phân quyền
│   │   │   ├── Auth/                       # Google OAuth & quên mật khẩu bằng OTP (PasswordResetController)
│   │   │   ├── AiChatController.php        # API trợ lý AI tư vấn sản phẩm (Gemini)
│   │   │   ├── ReviewController.php        # Đánh giá sản phẩm của khách đã mua
│   │   │   ├── Customer/                   # Tính năng khách hàng cá nhân (Đơn mua, Hủy đơn)
│   │   │   ├── Payments/                   # Cổng thanh toán trực tuyến (VNPAY, MoMo)
│   │   │   ├── AuthController.php          # Đăng nhập, đăng ký, xác thực OTP email/SĐT
│   │   │   ├── CartController.php          # Quản lý giỏ hàng & chọn món thanh toán
│   │   │   ├── ChatController.php          # API chat widget phía khách hàng
│   │   │   ├── CheckoutController.php      # Quy trình đặt hàng, tính phí ship, mã giảm giá
│   │   │   ├── HomeController.php          # Trang chủ, bộ sưu tập, banner
│   │   │   ├── OrderTrackingController.php # Tra cứu hành trình đơn hàng không cần đăng nhập
│   │   │   ├── ProductController.php       # Danh sách, lọc sản phẩm & trang chi tiết
│   │   │   ├── ProfileController.php       # Cập nhật thông tin cá nhân & mật khẩu
│   │   │   ├── ShippingController.php      # Tính phí và tạo vận đơn GHN
│   │   │   └── WishlistController.php      # Quản lý sản phẩm yêu thích
│   │   ├── Middleware/                     # Middleware kiểm tra quyền (Admin, Staff, Customer)
│   │   └── Requests/                       # Validation dữ liệu đầu vào (CheckoutRequest...)
│   ├── Models/                             # Eloquent Models (User, Product, Order, Chat, Coupon...)
│   └── Services/                           # Tầng nghiệp vụ độc lập
│       ├── CartService.php                 # Quản lý trạng thái giỏ hàng & áp dụng coupon
│       ├── SmsService.php                  # Dịch vụ gửi mã OTP qua SMS
│       ├── OtpSender.php                   # Gửi mã OTP qua email / SMS (đăng ký, quên mật khẩu)
│       ├── Ai/                             # GeminiService + ProductRecommender (gợi ý sản phẩm)
│       ├── Payments/                       # Logic tạo chữ ký số & gọi API VNPAY, MoMo
│       └── Shipping/                       # Logic tích hợp API Giao Hàng Nhanh (GHN)
├── bootstrap/                              # Khởi tạo ứng dụng & cấu hình pipeline
├── config/                                 # Cấu hình dịch vụ (database, services, auth...)
├── database/
│   ├── factories/                          # Factory sinh dữ liệu giả lập kiểm thử
│   ├── migrations/                         # Lịch sử các bước tạo & sửa bảng CSDL
│   └── seeders/                            # Bộ dữ liệu mẫu ban đầu (sản phẩm, tài khoản...)
├── public/                                 # Thư mục web gốc (index.php, tài nguyên build Vite)
├── resources/
│   ├── css/                                # Tailwind CSS tùy biến theo bộ nhận diện Mộc An
│   ├── js/                                 # Alpine.js, Chat Widget, Toast handler
│   └── views/                              # Hệ thống giao diện Blade Templates
│       ├── account/                        # Trang hồ sơ cá nhân
│       ├── admin/                          # Giao diện bảng điều khiển quản trị
│       ├── auth/                           # Form đăng nhập, đăng ký, xác thực OTP
│       ├── cart/                           # Giao diện giỏ hàng (hỗ trợ checkbox chọn món)
│       ├── checkout/                       # Giao diện thanh toán & đặt hàng thành công
│       ├── layouts/                        # Layout master (app.blade.php, admin.blade.php)
│       ├── orders/                         # Lịch sử & chi tiết đơn hàng
│       ├── partials/                       # Thành phần dùng chung (toast.blade.php)
│       ├── payments/                       # Màn hình thông báo kết quả giao dịch
│       ├── products/                       # Danh mục, bộ lọc & chi tiết sản phẩm
│       ├── wishlist/                       # Danh sách món đồ yêu thích
│       └── home.blade.php                  # Trang chủ Mộc An
├── routes/
│   ├── web.php                             # Định tuyến toàn bộ ứng dụng web & API nội bộ
│   └── console.php                         # Lệnh chạy dòng lệnh Artisan
├── storage/                                # File tải lên, log, cache; storage/picture chứa ảnh sản phẩm gốc
└── docker/                                 # Nginx, PHP-FPM, entrypoint và mẫu biến môi trường cho Render
```

---

## 3. Cấu trúc Cơ sở Dữ liệu Chính

1. **`users`**: Tài khoản người dùng (Khách hàng, Nhân viên, Quản trị viên).
2. **`categories`**: Danh mục nội thất phân cấp.
3. **`products`** & **`product_variants`**: Sản phẩm nội thất và các phiên bản kích thước/màu sắc/chất liệu.
4. **`product_images`**: Thư viện hình ảnh sản phẩm chất lượng cao.
5. **`orders`** & **`order_items`**: Đơn hàng, địa chỉ GHN, mã vận đơn và chi tiết sản phẩm đã mua.
6. **`coupons`**: Mã ưu đãi giảm giá theo phần trăm hoặc số tiền cố định.
7. **`payment_transactions`**: Bản ghi giao dịch thanh toán qua cổng VNPAY / MoMo.
8. **`chats`** & **`chat_messages`**: Tin nhắn trò chuyện trực tiếp giữa khách hàng và nhân viên.
9. **`wishlists`** & **`reviews`**: Danh sách sản phẩm quan tâm và đánh giá phản hồi từ khách.

---

## 4. Các tính năng nổi bật

### Phân hệ Khách hàng (Storefront)
- **Danh mục & Bộ lọc nâng cao**: Lọc sản phẩm theo danh mục, khoảng giá, chất liệu, màu sắc và sắp xếp.
- **Giỏ hàng linh hoạt**: Chọn từng sản phẩm thanh toán bằng checkbox, tự động tính tổng tiền và chiết khấu.
- **Phương thức thanh toán**:
  - Thanh toán khi nhận hàng (COD).
  - Cổng thanh toán VNPAY (Thẻ ATM, QR, Ngân hàng).
  - Ví điện tử MoMo (MoMo ATM).
- **Hệ thống Toast Notification**: Thông báo nổi góc màn hình tức thì khi đăng nhập, đặt hàng, thêm giỏ hàng thành công.
- **Live Chat Hỗ trợ**: Khách hàng đã đăng nhập có thể trò chuyện trực tiếp với tư vấn viên, lưu trữ lịch sử cuộc gọi.
- **Trợ lý AI tư vấn (Gemini)**: Tab "Tư vấn AI" trong khung chat, mở cho cả khách chưa đăng nhập; AI chỉ gợi ý sản phẩm đang có trong kho và hiển thị thẻ sản phẩm. Khi chưa cấu hình `GEMINI_API_KEY`, hệ thống tự gợi ý theo từ khóa.
- **Đánh giá sản phẩm**: Chỉ khách có đơn hàng chứa sản phẩm ở trạng thái "Hoàn thành" mới được đánh giá (1-5 sao + nhận xét); hiển thị điểm trung bình trên trang chi tiết và thẻ sản phẩm.
- **Quên mật khẩu bảo mật**: Mã OTP 6 số gửi qua email hoặc SMS, lưu dạng băm, hết hạn sau 10 phút, tối đa 5 lần nhập sai, giới hạn tần suất gửi; đổi mật khẩu xong sẽ đăng xuất mọi phiên khác và gửi email thông báo.
- **Tích hợp GHN**: Tính phí giao hàng tự động theo Quận/Huyện/Xã và tra cứu mã vận đơn thực tế.

### Phân hệ Quản trị (Admin Portal - `/admin`)
- **Dashboard & Báo cáo**: Thống kê doanh thu, đơn hàng, biểu đồ tăng trưởng và xuất báo cáo PDF/Excel.
- **Quản lý Bán hàng**: Cập nhật trạng thái đơn hàng, đẩy đơn sang bưu cục GHN 1-click.
- **Live Chat CSKH**: Tiếp nhận cuộc trò chuyện, phân công nhân viên, hiển thị tin nhắn mới nhất và chỉ hiển thị khách hàng đã gửi tin nhắn.
- **Quản lý Tài chính**: Theo dõi dòng tiền, trạng thái giao dịch cổng thanh toán.

---

## 5. Hướng dẫn Cài đặt & Vận hành

### Yêu cầu môi trường
- PHP >= 8.2 (Extensions: `pdo`, `sqlite3` hoặc `mysqli`, `mbstring`, `openssl`, `curl`)
- Composer >= 2.x
- Node.js >= 18.x & NPM

### Các bước cài đặt
```bash
# 1. Cài đặt các thư viện PHP & Node.js
composer install
npm install

# 2. Cấu hình môi trường
cp .env.example .env
php artisan key:generate

# 3. Khởi tạo cơ sở dữ liệu và nạp dữ liệu mẫu
php artisan migrate --seed

# 4. Biên dịch tài nguyên giao diện
npm run build

# 5. Khởi chạy máy chủ phát triển
php artisan serve
```

Truy cập website tại: `http://127.0.0.1:8000`

Khi chạy bằng XAMPP (MySQL): tạo database `utf8mb4_unicode_ci` trong phpMyAdmin rồi sửa `DB_CONNECTION=mysql`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` trong `.env`. Nạp danh mục sản phẩm có ảnh thật bằng `php artisan db:seed --class=PictureProductSeeder`.

Lỗi `Vite manifest not found`: chạy `npm run build` (thư mục `public/build` không còn được commit).

### Triển khai lên Render (Docker)
- Render build từ `Dockerfile`: tự cài Composer, build Vite (Node 22) và đóng gói ảnh trong `storage/picture`.
- Khai báo biến môi trường theo mẫu `docker/render.env.example`. Bắt buộc: `APP_KEY`, `APP_URL`, thông tin MySQL, SMTP (`MAIL_*`, vì mã OTP không còn hiện trên màn hình ở production), `GHN_TOKEN`/`GHN_SHOP_ID`; tùy chọn: `GEMINI_API_KEY`, `SMS_PROVIDER`, VNPAY/MoMo, Google OAuth.
- Ảnh catalog trong `storage/picture` được đóng vào image và được chép lại nếu thiếu. Ảnh admin tải thêm nằm trên đĩa container: vào Render → Disks, tạo Persistent Disk và mount tại `/var/www/storage` thì ảnh đó còn sau mỗi lần deploy.
- Giữ nguyên `APP_KEY` và `DB_HOST` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD`. `RUN_SEEDERS` phải là `false`. Không chạy `migrate:fresh`.

---

## 6. Tài khoản Thử nghiệm Mẫu

- **Tài khoản Quản trị (Admin)**:
  - Email: `admin@furniture.com`
  - Mật khẩu: `password123`
- **Tài khoản Khách hàng (Customer)**:
  - Email: `customer@example.com`
  - Mật khẩu: `password123`
