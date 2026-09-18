# Kế Hoạch Triển Khai Product Catalog (Mộc An)

## 1. Goal
Xây dựng trang Catalog sản phẩm thực tế tại `GET /san-pham` (named route `products.index`) cho phép người dùng xem danh sách sản phẩm, tìm kiếm theo tên, lọc theo danh mục, sắp xếp theo giá/mới nhất, phân trang giữ nguyên query string, hiển thị empty state khi không có kết quả, và hỗ trợ toàn diện 5 theme màu giao diện.

## 2. Architecture
- **Route**: `GET /san-pham` -> `ProductController@index` (named `products.index`).
- **Controller (`ProductController@index`)**:
  - Nhận các query params: `q` (search string), `category` (category slug), `sort` (whitelist: `latest`, `price_asc`, `price_desc`).
  - Query Model `Product`:
    - Eager load: `category`, `primaryImage`.
    - Điều kiện: `is_active = true`.
    - Search: `when($request->filled('q'), fn($query) => $query->where('name', 'like', '%' . trim($request->input('q')) . '%'))`.
    - Category filter: `when($request->filled('category'), fn($query) => $query->whereHas('category', fn($q) => $q->where('slug', $request->input('category'))->where('is_active', true)))`.
    - Sort:
      - `latest` (default hoặc fallback): `orderBy('created_at', 'desc')`
      - `price_asc`: `orderBy('base_price', 'asc')`
      - `price_desc`: `orderBy('base_price', 'desc')`
    - Pagination: `->paginate(12)->withQueryString()`.
  - Lấy danh sách danh mục active để hiển thị dropdown filter:
    - `Category::where('is_active', true)->orderBy('name')->get()`.
- **View (`resources/views/products/index.blade.php`)**:
  - Kế thừa `layouts.app`.
  - Header / Breadcrumb / Title section ("Sản phẩm", "Nội thất tuyển chọn cho không gian sống").
  - Toolbar: Search form (`method="GET"`, action `route('products.index')`), Category filter select, Sort select, Reset filters button (khi có query param).
  - Product Grid: 4 cột desktop, 2-3 cột tablet, 1-2 cột mobile. Tận dụng style card semantic token (`bg-surface`, `border-ui-border`, `text-heading`, `text-body`, `text-muted`, `bg-accent`).
  - Phân trang: Tailwind pagination với query string preserved.
  - Empty state: Khi danh sách trống, hiển thị thông báo "Không tìm thấy sản phẩm phù hợp." và nút "Xóa bộ lọc".

## 3. Files Create / Modify
### Create:
- `tests/Feature/ProductCatalogTest.php` (Feature tests: Guest & Auth access, list active products, search, category filter, sort, pagination, empty state, theme tokens).
- `resources/views/products/index.blade.php` (Catalog view template).
- `resources/views/components/product-card.blade.php` hoặc Blade partial nếu cần chia sẻ với home.

### Modify:
- `routes/web.php` (Bổ sung `Route::get('/san-pham', [ProductController::class, 'index'])->name('products.index')`).
- `app/Http/Controllers/ProductController.php` (Hiện thực hàm `index(Request $request)`).
- `resources/views/layouts/app.blade.php` (Cập nhật nav link Sản phẩm -> `route('products.index')`, active state dynamic `request()->routeIs(...)`).
- `resources/views/home.blade.php` (Cập nhật CTA "Khám phá sản phẩm" -> `route('products.index')`).

## 4. Query Contract
- `GET /san-pham`
- `q`: Search keyword (`trim`).
- `category`: Category slug.
- `sort`: `latest` (default), `price_asc`, `price_desc`.
- Hỗ trợ kết hợp: `?q=ban&category=phong-khach&sort=price_desc&page=2`.

## 5. Tests Plan (TDD)
- `test_guest_can_view_product_catalog()`
- `test_authenticated_user_can_view_product_catalog()`
- `test_catalog_only_displays_active_products()`
- `test_catalog_displays_product_details_and_primary_image()`
- `test_catalog_can_search_products_by_name()`
- `test_catalog_can_filter_by_category_slug()`
- `test_catalog_handles_invalid_category_slug_gracefully()`
- `test_catalog_sorts_by_latest_by_default()`
- `test_catalog_sorts_by_price_ascending()`
- `test_catalog_sorts_by_price_descending()`
- `test_catalog_invalid_sort_falls_back_to_latest()`
- `test_catalog_pagination_preserves_query_string()`
- `test_catalog_shows_empty_state_when_no_products_found()`
- `test_catalog_uses_semantic_theme_tokens()`

## 6. UI & Semantic Tokens
- `bg-page`, `bg-surface`, `bg-surface-alt`
- `text-heading`, `text-body`, `text-muted`
- `border-ui-border`
- `bg-primary`, `text-primary-foreground`
- `bg-accent`, `text-accent-foreground`

## 7. Verification
- `php artisan test` (32 tests cũ + bộ test mới, 0 fail).
- `npm run build` (PASS).
- `git diff --check` (PASS).
