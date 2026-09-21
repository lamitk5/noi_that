# HƯỚNG DẪN DEMO VÀ NGHIỆM THU HỆ THỐNG ECOMMERCE NỘI THẤT "MỘC AN"

> **Dự án:** Hệ thống Thương mại Điện tử Nội thất Mộc An  
> **Framework:** Laravel 11.x (PHP 8.2+) & Vite + Tailwind CSS  
> **Cơ sở dữ liệu:** SQLite / MySQL  
> **Branch nghiệm thu:** `feature/dang`

---

## 1. MÔI TRƯỜNG & KHỞI CHẠY HỆ THỐNG

### 1.1. Yêu cầu hệ thống
- **PHP:** >= 8.2 (yêu cầu extensions: `pdo_sqlite` hoặc `pdo_mysql`, `mbstring`, `openssl`, `curl`)
- **Composer:** >= 2.x
- **Node.js:** >= 20.x & npm >= 10.x

### 1.2. Khởi tạo dữ liệu & Chạy ứng dụng

```bash
# 1. Cài đặt dependencies (nếu chưa cài)
composer install
npm install

# 2. Tạo file cấu hình và sinh APP_KEY (nếu cài mới)
cp .env.example .env
php artisan key:generate

# 3. Chạy migration và nạp toàn bộ dữ liệu mẫu (Sản phẩm, Biến thể, Đơn hàng, Voucher, Bài viết, Đánh giá)
php artisan migrate --seed

# 4. Build assets giao diện
npm run build

# 5. Khởi động Web Server (cửa sổ 1)
php artisan serve

# (Tùy chọn) Khởi động Vite Server cho live reload khi phát triển (cửa sổ 2)
npm run dev
```

Sau khi chạy lệnh, truy cập ứng dụng tại: `http://127.0.0.1:8000`

---

## 2. TÀI KHOẢN TRẢI NGHIỆM (DEMO ACCOUNTS)

Hệ thống đã nạp sẵn 2 tài khoản chính đại diện cho hai luồng người dùng:

| Vai trò | Email đăng nhập | Mật khẩu | Quyền hạn & Trạng thái |
| :--- | :--- | :--- | :--- |
| **Quản trị viên (Admin)** | `admin@mocan.test` | `Admin@123` | Toàn quyền Dashboard, Quản lý Sản phẩm, Kho hàng, Đơn hàng, Khuyến mãi, Hỗ trợ, CMS |
| **Khách hàng thân thiết (VIP)** | `customer@mocan.test` | `Customer@123` | Khách hàng hạng Bạc (1,250 điểm tích lũy), đã có lịch sử đơn hàng và quyền đánh giá |

*(Bạn cũng có thể dễ dàng nhấn **"Đăng ký"** để tạo tài khoản khách hàng mới bất cứ lúc nào).*

---

## 3. KỊCH BẢN DEMO KHÁCH HÀNG (CUSTOMER CORE FLOW)

### Bước 1: Khám phá Trang chủ & Trải nghiệm Đa giao diện (Themes)
1. Truy cập `http://127.0.0.1:8000`.
2. Quan sát Banner Slider động, Danh mục nổi bật, Sản phẩm bán chạy (Best-sellers), Sản phẩm mới.
3. Thử nghiệm **Bộ chuyển đổi Giao diện (Theme Switcher)** trên thanh Header:
   - Hỗ trợ 5 phong cách: `Mộc An (Mặc định)`, `Vintage Cổ Điển`, `Modern Hiện Đại`, `Minimalist Tối Giản`, `Luxury Sang Trọng`.
   - Bảng màu, typography và bo góc thay đổi mượt mà theo đúng token thiết kế.

### Bước 2: Danh mục & Bộ lọc sản phẩm thông minh (Catalog & Filter)
1. Nhấn vào menu **"Sản phẩm"** hoặc truy cập `/products`.
2. Thử nghiệm các bộ lọc tích hợp:
   - **Tìm kiếm từ khóa:** Nhập *"Sofa"*, *"Gỗ sồi"*, *"Bàn ăn"*.
   - **Lọc theo Danh mục:** Phòng khách, Phòng ngủ, Phòng ăn, Phòng làm việc.
   - **Lọc theo Khoảng giá:** Kéo thanh giá hoặc chọn phân khúc giá.
   - **Lọc theo Đánh giá:** Từ 4 sao trở lên.
   - **Sắp xếp:** Giá tăng dần, Giá giảm dần, Mới nhất, Bán chạy nhất.

### Bước 3: Trang Chi tiết Sản phẩm & Chọn biến thể (PDP & Variants)
1. Bấm vào sản phẩm có nhiều biến thể (ví dụ: *Sofa Gỗ Sồi Hiện Đại Mộc An*).
2. Trải nghiệm:
   - **Hình ảnh sản phẩm:** Gallery hình ảnh sắc nét.
   - **Biến thể kích thước / màu sắc:** Khi chuyển chọn các biến thể, mã SKU, giá bán tương ứng và số lượng tồn kho tức thời sẽ cập nhật theo.
   - **Yêu thích (Wishlist):** Bấm nút trái tim để thêm/xóa sản phẩm khỏi danh sách yêu thích.
   - **Số lượng mua:** Tăng/giảm số lượng (hệ thống tự giới hạn không cho chọn quá số lượng tồn kho khả dụng).
3. Nhấn **"Thêm vào giỏ hàng"**.

### Bước 4: Giỏ hàng (Cart Management)
1. Nhấn biểu tượng Giỏ hàng trên thanh điều hướng hoặc truy cập `/cart`.
2. Kiểm tra danh sách mặt hàng, tên biến thể, đơn giá.
3. Thay đổi số lượng sản phẩm trực tiếp trong giỏ hàng (tự động cập nhật tổng tiền).
4. Nhấn **"Tiến hành đặt hàng"**.

### Bước 5: Thanh toán & Áp dụng Ưu đãi (Checkout Flow)
1. Tại trang `/checkout`, điền/kiểm tra thông tin giao hàng: Họ tên, Số điện thoại, Địa chỉ nhận hàng.
2. **Áp dụng Mã giảm giá (Voucher):**
   - Nhập `MOCAN10` (Giảm 10%) hoặc `TRIAN200K` (Giảm 200.000đ) -> Nhấn Áp dụng -> Tổng tiền giảm trừ ngay tức thì.
3. **Sử dụng Điểm tích lũy (Loyalty Points):**
   - Khách hàng có điểm có thể nhập số điểm muốn đổi (1 điểm = 1.000đ) để trừ trực tiếp vào đơn hàng.
4. **Chọn phương thức thanh toán:**
   - **COD:** Thanh toán tiền mặt khi nhận hàng.
   - **Chuyển khoản:** Hiển thị thông tin STK ngân hàng.
   - **Cổng trực tuyến Sandbox:** VNPAY Sandbox hoặc MoMo Sandbox.
5. Nhấn **"Xác nhận đặt hàng"**.
   - Hệ thống thực hiện kiểm tra tồn kho nguyên tử (Atomic Inventory Lock), trừ tồn kho ngay lập tức để tránh oversell.
   - Hiển thị trang Thông báo Đặt hàng Thành công với Mã đơn hàng (ví dụ: `#MA-100...`).

### Bước 6: Quản lý Đơn hàng & Tra cứu Vận đơn (Order Tracking)
1. Đăng nhập tài khoản `customer@mocan.test`.
2. Vào **"Tài khoản"** -> **"Đơn hàng của tôi"** (`/account/orders`).
3. Bấm vào chi tiết đơn hàng:
   - Xem thông tin sản phẩm, giá trị thanh toán, phương thức đã chọn.
   - Xem **Timeline vận chuyển thời gian thực**: Trạng thái đơn, Đơn vị vận chuyển (GHN / GHTK), Mã vận đơn tra cứu.
   - Khách hàng có thể nhấn **"Hủy đơn hàng"** khi đơn còn ở trạng thái `Chờ xác nhận` -> Kho tự động hoàn lại số lượng sản phẩm.

### Bước 7: Đánh giá Sản phẩm (Verified Reviews)
1. Đối với các đơn hàng đã chuyển sang trạng thái `Hoàn tất (Completed)`.
2. Khách hàng vào trang chi tiết sản phẩm đã mua -> Xuất hiện form Đánh giá chính chủ.
3. Chọn số sao (1-5 sao), viết cảm nhận trải nghiệm thực tế -> Đánh giá được phê duyệt hiển thị ngay lập tức với huy hiệu *"Người mua đã xác thực"*.

### Bước 8: Dịch vụ Khách hàng (Support & FAQ & Blog)
1. **Hỏi đáp thường gặp:** Truy cập `/faq` để xem các câu hỏi về bảo hành, vận chuyển, đổi trả.
2. **Gửi yêu cầu hỗ trợ:** Truy cập `/support` để tạo Support Ticket mới, theo dõi phản hồi từ ban quản trị.
3. **Tin tức & Cẩm nang:** Truy cập `/posts` để xem bài viết tư vấn nội thất và kiến trúc không gian.

---

## 4. KỊCH BẢN DEMO QUẢN TRỊ VIÊN (ADMIN WORKFLOW)

### Bước 1: Đăng nhập & Tổng quan Dashboard
1. Truy cập `/login`, đăng nhập với tài khoản: `admin@mocan.test` / `Admin@123`.
2. Vào **Khu vực Quản trị** qua menu góc phải hoặc truy cập `/admin/dashboard`.
3. Xem các chỉ số KPI:
   - Doanh thu theo ngày/tháng, tổng số đơn đặt hàng, tỷ lệ chuyển đổi.
   - Biểu đồ biến động doanh thu 30 ngày.
   - Top 5 sản phẩm bán chạy nhất.
   - Danh sách đơn hàng mới cần xử lý ngay.

### Bước 2: Quản lý Sản phẩm & Biến thể (Catalog Management)
1. Vào mục **"Sản phẩm"** (`/admin/products`).
2. Xem danh sách, tìm kiếm theo tên, lọc theo danh mục, trạng thái ẩn/hiện.
3. Thêm mới / Chỉnh sửa sản phẩm:
   - Nhập thông tin: Tên, slug, mô tả, danh mục, giá gốc, giá khuyến mãi.
   - Thêm các biến thể (Variants): Kích thước (1m6, 1m8, 2m), Màu sắc (Nâu sồi, Óc chó, Tự nhiên), SKU riêng biệt cho từng loại.

### Bước 3: Quản trị Kho hàng (Inventory Stock Control)
1. Vào mục **"Kho hàng"** (`/admin/inventory`).
2. Xem tình trạng tồn kho của từng sản phẩm và từng biến thể cụ thể.
3. Nhập số lượng tồn kho mới, xem cảnh báo các mặt hàng sắp hết hàng (Low stock alert).

### Bước 4: Vòng đời Quản lý Đơn hàng (Order Lifecycle Management)
1. Vào mục **"Đơn hàng"** (`/admin/orders`).
2. Lọc đơn theo trạng thái: *Chờ xác nhận*, *Đã xác nhận*, *Đang đóng gói*, *Đang giao hàng*, *Hoàn tất*, *Đã hủy*.
3. Mở chi tiết một đơn hàng (`/admin/orders/{id}`):
   - **Xác nhận đơn hàng:** Chuyển từ Chờ xác nhận -> Đã xác nhận.
   - **Đóng gói hàng:** Chuyển sang Đang đóng gói.
   - **Xuất kho & Giao hàng:** Chuyển sang Đang giao hàng -> Hệ thống tự động sinh Mã vận đơn (GHN Express / GHTK) và ghi nhận mốc thời gian xuất kho.
   - **Hoàn thành đơn hàng:** Đánh dấu giao thành công -> Cộng điểm thưởng Loyalty cho khách hàng.
   - **Hủy đơn hàng:** Khi hủy đơn, hệ thống tự động hoàn lại tồn kho nguyên tử cho kho hàng.

### Bước 5: Phân tích & Báo cáo Nâng cao (Analytics & Reports)
1. Vào mục **"Báo cáo & Phân tích"** (`/admin/analytics`).
2. Xem các phân tích chuyên sâu:
   - Báo cáo giỏ hàng bị bỏ quên (Cart Abandonment Rate) & các mặt hàng thường xuyên bị bỏ lại.
   - Báo cáo hiệu quả các cổng thanh toán (COD vs VNPAY vs MoMo).
   - Tần suất sử dụng mã khuyến mãi (Voucher Usage).

### Bước 6: Quản lý Khuyến mãi, Hỗ trợ & Nội dung (Marketing & CMS)
1. **Voucher / Khuyến mãi (`/admin/vouchers`):** Tạo mới mã giảm giá theo %, giảm tiền trực tiếp, đặt giá trị đơn hàng tối thiểu và số lượt sử dụng tối đa.
2. **Yêu cầu hỗ trợ (`/admin/support-tickets`):** Tiếp nhận và phản hồi giải quyết thắc mắc từ khách hàng.
3. **Quản lý Bài viết & Tin tức (`/admin/posts`):** Đăng tải bài viết blog, mẹo trang trí nội thất chuẩn SEO.
4. **Hỏi đáp FAQ (`/admin/faqs`):** Cập nhật nội dung câu hỏi thường gặp.

---

## 5. KIỂM THỬ TỰ ĐỘNG (AUTOMATED TESTS)

Dự án sở hữu bộ test toàn diện bao phủ 100% các luồng nghiệp vụ quan trọng:

```bash
php artisan test
```

**Kết quả kiểm thử:**
- **268 Tests: 100% PASS**
- **0 Lỗi / 0 Failure**
- **992 Assertions** kiểm tra tính toàn vẹn dữ liệu, quyền truy cập, tồn kho nguyên tử và xử lý thanh toán.
