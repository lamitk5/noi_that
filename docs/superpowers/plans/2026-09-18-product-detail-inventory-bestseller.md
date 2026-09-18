# Implementation Plan - Phase 2: Product Detail + Inventory + Best Seller

Phase 2 builds the real Product Detail page (`GET /san-pham/{slug}`), inventory status calculation from existing `product_variants.stock`, and best-seller calculation from real `order_items.quantity`.

## Schema Audit & Inventory/Sales Sources
- **Product Stock Source:** `product_variants.stock` (sum of active variants). No migration needed.
- **Product Price Source:** `products.base_price` and `product_variants.price`. No sale_price in schema.
- **Product Image Source:** `product_images.image_path` (`is_primary` for primary image, all images ordered by `sort_order` for gallery).
- **Product Variant Source:** `product_variants` (color, size, material, price, stock, sku).
- **Best Seller Source:** `SUM(order_items.quantity)` grouped by product through `product_variants` and filtered by valid non-canceled, non-failed orders.
- **Order Success Status:** `order_status in ['completed', 'confirmed', 'shipping']` and `payment_status != 'failed'`.
- **Migrations Added:** NONE.

## Proposed Changes

### Models
- [`app/Models/Product.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/app/Models/Product.php):
  - Add `orderItems()` relation: `hasManyThrough(OrderItem::class, ProductVariant::class)`.
  - Add inventory helper methods: `totalStock()`, `isOutOfStock()`, `isLowStock()`, `stockStatusText()`.
  - Add `scopeBestSelling($query, int $limit = 4)`.
- [`app/Models/Order.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/app/Models/Order.php):
  - Add status constants and scopes.

### Routes & Controllers
- [`routes/web.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/routes/web.php):
  - Add `Route::get('/san-pham/{product:slug}', [ProductController::class, 'show'])->name('products.show')`.
- [`app/Http/Controllers/ProductController.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/app/Http/Controllers/ProductController.php):
  - Add `show(Product $product)`: 404 on inactive, eager load `category`, `images`, `primaryImage`, `variants`, load 4 related products from same category.
- [`app/Http/Controllers/HomeController.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/app/Http/Controllers/HomeController.php):
  - Query real best sellers (`Product::bestSelling(4)->with(['category', 'primaryImage'])->get()`) and pass to view.

### Views
- [NEW] [`resources/views/products/show.blade.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/resources/views/products/show.blade.php):
  - 2-column layout (gallery left, info right), breadcrumbs, variant selector (Alpine.js), stock badges, related products, 5-theme semantic tokens.
- [`resources/views/products/index.blade.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/resources/views/products/index.blade.php):
  - Connect product image and name to `route('products.show', $product->slug)`.
- [`resources/views/home.blade.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/resources/views/home.blade.php):
  - Connect featured products to `route('products.show', $product->slug)`.
  - Add Best Seller section, displayed only if `$bestSellers->isNotEmpty()`.

### Tests
- [NEW] `tests/Feature/ProductDetailTest.php`
- [NEW] `tests/Feature/ProductInventoryTest.php`
- [NEW] `tests/Feature/BestSellerTest.php`

## Verification Plan
1. `php artisan test tests/Feature/ProductDetailTest.php` (RED -> GREEN)
2. `php artisan test tests/Feature/ProductInventoryTest.php` (GREEN)
3. `php artisan test tests/Feature/BestSellerTest.php` (GREEN)
4. `php artisan test` (all tests passing)
5. `npm run build`
6. `git diff --check`
7. Commit and push to `origin/feature/dang`
