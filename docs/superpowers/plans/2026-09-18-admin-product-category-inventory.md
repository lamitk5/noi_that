# Implementation Plan - Phase 8: Admin Product + Category + Variant + Inventory Management

## Goal
Provide a comprehensive, secure back-office admin suite for "Mộc An" furniture ecommerce, allowing administrators to manage categories, products, product variants, product images, and real-time inventory stock without disrupting historical orders, verified reviews, or storefront customer flows.

## Schema Audit Summary
- **Categories** (`categories`):
  - `id`, `name`, `slug` (unique), `description`, `is_active` (boolean, default true), `timestamps`.
- **Products** (`products`):
  - `id`, `category_id` (FK categories), `name`, `slug` (unique), `sku` (unique), `short_description`, `description`, `base_price`, `is_active` (boolean, default true), `timestamps`.
- **Product Variants** (`product_variants`):
  - `id`, `product_id` (FK products), `color`, `size`, `material`, `price`, `stock` (unsigned int, default 0), `sku` (unique), `timestamps`.
  - Authoritative source of stock: `product_variants.stock`.
- **Product Images** (`product_images`):
  - `id`, `product_id` (FK products), `image_path`, `is_primary` (boolean, default false), `sort_order` (unsigned int, default 0), `timestamps`.
- **Migration Policy**: **NONE**. Existing schema satisfies all domain requirements cleanly.

## Information Architecture & Routes
All routes protected under `middleware(['auth', 'admin'])` with prefix `admin.` and URI `/admin/...`:

### 1. Categories (`admin.categories.*`)
- `GET /admin/danh-muc` -> `Admin\CategoryController@index` (`admin.categories.index`)
- `GET /admin/danh-muc/them` -> `Admin\CategoryController@create` (`admin.categories.create`)
- `POST /admin/danh-muc` -> `Admin\CategoryController@store` (`admin.categories.store`)
- `GET /admin/danh-muc/{category}/chinh-sua` -> `Admin\CategoryController@edit` (`admin.categories.edit`)
- `PATCH /admin/danh-muc/{category}` -> `Admin\CategoryController@update` (`admin.categories.update`)
- `DELETE /admin/danh-muc/{category}` -> `Admin\CategoryController@destroy` (`admin.categories.destroy`)
  - Safety check: If category has products (`$category->products()->exists()`), deletion is rejected with 422/redirect error ("Không thể xóa danh mục đang có sản phẩm").

### 2. Products (`admin.products.*`)
- `GET /admin/san-pham` -> `Admin\ProductController@index` (`admin.products.index`)
  - Search by name / sku (`q`)
  - Filter by category (`category`)
  - Filter by status (`status`: active / inactive)
  - Filter by stock (`stock`: in_stock, low_stock, out_of_stock)
  - Pagination 15 per page with preserved query parameters
- `GET /admin/san-pham/them` -> `Admin\ProductController@create` (`admin.products.create`)
- `POST /admin/san-pham` -> `Admin\ProductController@store` (`admin.products.store`)
  - Auto-generate deterministic unique slug from `name`
  - Validates `category_id`, `name`, `sku` (unique), `base_price`, descriptions, `is_active`
- `GET /admin/san-pham/{product}/chinh-sua` -> `Admin\ProductController@edit` (`admin.products.edit`)
  - Product edit form with tabs or sections for General Info, Variants table/manager, and Image gallery
- `PATCH /admin/san-pham/{product}` -> `Admin\ProductController@update` (`admin.products.update`)
  - Updates core details; keeps slug stable unless explicitly modified
- `PATCH /admin/san-pham/{product}/trang-thai` -> `Admin\ProductController@toggleStatus` (`admin.products.toggle-status`)
  - Toggles `is_active` between true/false (storefront immediately hides/shows product)
- Deletion policy: Hard delete is disabled to preserve historical order items and reviews.

### 3. Variants (`admin.products.variants.*`)
- `POST /admin/san-pham/{product}/bien-the` -> `Admin\ProductVariantController@store` (`admin.products.variants.store`)
  - Validates `sku` (unique in `product_variants`), `color`, `size`, `material`, `price` (nullable, defaults to base_price or explicit), `stock` (min: 0)
- `PATCH /admin/san-pham/{product}/bien-the/{variant}` -> `Admin\ProductVariantController@update` (`admin.products.variants.update`)
  - Scoped to product (`abort_if($variant->product_id !== $product->id, 404)`)
  - Validates unique SKU (except current variant ID), attributes, price, stock
- `DELETE /admin/san-pham/{product}/bien-the/{variant}` -> `Admin\ProductVariantController@destroy` (`admin.products.variants.destroy`)
  - Safety check: If `$variant->orderItems()->exists()`, deletion is blocked with message: "Không thể xóa biến thể đã phát sinh đơn hàng lịch sử." If no orders exist, safe delete is performed.

### 4. Images (`admin.products.images.*`)
- `POST /admin/san-pham/{product}/hinh-anh` -> `Admin\ProductImageController@store` (`admin.products.images.store`)
  - Validates image file (jpeg, jpg, png, webp, max 5MB)
  - Stores into `public` disk under `products/` with UUID/unique filename
  - Saves record in `product_images`. If first image, sets `is_primary = true`.
- `PATCH /admin/san-pham/{product}/hinh-anh/{image}/primary` -> `Admin\ProductImageController@setPrimary` (`admin.products.images.set-primary`)
  - Atomic transaction: sets all other images for product to `is_primary = false`, sets target image to `true`.
- `DELETE /admin/san-pham/{product}/hinh-anh/{image}` -> `Admin\ProductImageController@destroy` (`admin.products.images.destroy`)
  - Deletes DB row, cleans up storage file if it is an app-managed file
  - If deleted image was primary, promotes next image (by `sort_order` or `id`) to primary.

### 5. Inventory Management (`admin.inventory.*`)
- `GET /admin/kho-hang` -> `Admin\InventoryController@index` (`admin.inventory.index`)
  - Displays list of variants with product name, SKU, color, size, material, current stock, stock badge
  - Search by product name, product SKU, variant SKU
  - Filter by category, stock status (`all`, `in_stock`, `low_stock`, `out_of_stock`)
  - Uses centralized low stock threshold (`config('shop.low_stock_threshold', 5)`)
- `PATCH /admin/kho-hang/{variant}` -> `Admin\InventoryController@update` (`admin.inventory.update`)
  - Validates `stock` (integer, min: 0)
  - Uses `DB::transaction()` with `lockForUpdate()`
  - Sets absolute stock value

## Deletion & Data Preservation Strategy
1. **Orders & Order Items**:
   - `OrderItem` table stores snapshot data (`product_name`, `variant_info`, `price`).
   - Admin changes to product or variant do not overwrite past order item records.
   - Variants with existing `orderItems` cannot be hard-deleted.
2. **Reviews**:
   - Deactivating a product does not delete reviews.
   - Reactivating a product restores public visibility of past reviews and ratings.
3. **Categories**:
   - Categories with linked products cannot be deleted.

## Centralized Low Stock Configuration
Create `config/shop.php`:
```php
return [
    'low_stock_threshold' => (int) env('SHOP_LOW_STOCK_THRESHOLD', 5),
];
```
Both `Product::lowStockThreshold()` and `Admin\InventoryController` reference this single configuration point.

## Testing Strategy
1. `tests/Feature/Admin/CategoryManagementTest.php`:
   - Guest blocked, customer 403, admin access 200.
   - Category listing, creation, unique slug generation, editing, active toggle.
   - Prevention of deletion when category contains products.
   - Safe deletion of empty category.
2. `tests/Feature/Admin/ProductManagementTest.php`:
   - Guest blocked, customer 403, admin access 200.
   - Product creation with unique slug & SKU.
   - Product edit, price update, category reassignment.
   - Deactivate product: removed from Catalog, Product detail 404, blocked from Cart addition.
   - Reactivate product: restored to Catalog and Detail.
   - Search & filters (name, SKU, category, status, stock).
3. `tests/Feature/Admin/ProductVariantManagementTest.php`:
   - Variant creation with unique SKU, color, size, price, stock (min: 0).
   - Variant update, SKU conflict prevention.
   - Cross-product variant modification blocked (404).
   - Variant with order history cannot be hard-deleted.
   - Variant without order history can be deleted safely.
4. `tests/Feature/Admin/ProductImageManagementTest.php`:
   - Admin uploads image (fake storage).
   - Invalid MIME type rejected.
   - Primary image selection (only one primary allowed).
   - Image deletion with storage file cleanup.
   - Deleting primary promotes next available image to primary.
5. `tests/Feature/Admin/InventoryManagementTest.php`:
   - Inventory list renders all variants with product info.
   - Search by product name / SKU.
   - Filter by out-of-stock and low-stock.
   - Stock update: zero allowed, negative blocked.
   - Stock update reflected immediately in Product Detail and Cart validation.
