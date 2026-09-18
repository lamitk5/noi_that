# Implementation Plan - Phase 4: Shopping Cart (Giỏ hàng thực tế)

Phase 4 builds the full shopping cart functionality using session-based storage, real inventory validation, variant selection, quantity calculation, header badge synchronization, and cart page management.

## Schema & Architectural Decisions
- **Cart Storage:** Session-based cart (`session('cart', [])`).
  - Mapping: `variant_id => quantity`.
  - Guest and Authenticated users share the same clean contract.
  - Zero database migrations needed (MIGRATIONS = NONE).
- **Price Source:** `$variant->price ?? $variant->product->base_price`.
- **Stock Source:** `product_variants.stock`.
- **Cart Rules:**
  - Cannot add out-of-stock items (`variant.stock <= 0`).
  - Total quantity in cart cannot exceed `variant.stock`.
  - Duplicate additions accumulate quantity, capped by available stock (exceeding stock is rejected with clear error).
  - Inactive products/variants cannot be added.
  - Cart actions DO NOT decrement inventory (decrement is deferred to Phase 5 Checkout).

## Proposed Changes

### Services
- [NEW] [`app/Services/CartService.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/app/Services/CartService.php):
  - `getItems(): Collection` (eager loads variants with product, category, and primaryImage).
  - `add(int $variantId, int $quantity = 1): void` (validates stock, accumulated quantity, active state).
  - `update(int $variantId, int $quantity): void` (validates stock, updates quantity).
  - `remove(int $variantId): void` (removes variant from cart).
  - `clear(): void` (clears cart session).
  - `count(): int` (sums all quantities in cart).
  - `subtotal(): float` (calculates total cart value).

### Providers
- [`app/Providers/AppServiceProvider.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/app/Providers/AppServiceProvider.php):
  - View composer sharing `cartCount` using `CartService::count()`.

### Routes & Controllers
- [`routes/web.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/routes/web.php):
  - `GET /gio-hang` -> `CartController::index` (`cart.index`)
  - `POST /gio-hang` -> `CartController::store` (`cart.store`)
  - `PATCH /gio-hang/{variant}` -> `CartController::update` (`cart.update`)
  - `DELETE /gio-hang/{variant}` -> `CartController::destroy` (`cart.destroy`)
  - `DELETE /gio-hang` -> `CartController::clear` (`cart.clear`)
- [`app/Http/Controllers/CartController.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/app/Http/Controllers/CartController.php):
  - Delegates all cart operations to `CartService` and redirects with feedback messages.

### Views
- [NEW] [`resources/views/cart/index.blade.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/resources/views/cart/index.blade.php):
  - 2-column layout (items left, order summary right).
  - Empty state with "Tiếp tục mua sắm" CTA.
  - Quantity update form and item remove button.
  - Semantic theme tokens compatible with all 5 themes.
- [`resources/views/products/show.blade.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/resources/views/products/show.blade.php):
  - Enable "Thêm vào giỏ" button inside form POST `route('cart.store')`.
  - Bind hidden inputs `variant_id` and `quantity` with Alpine.js state.
- [`resources/views/layouts/app.blade.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/resources/views/layouts/app.blade.php):
  - Link cart button to `route('cart.index')` and display dynamic `$cartCount`.

### Tests
- [NEW] [`tests/Feature/CartTest.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/tests/Feature/CartTest.php):
  - Guest access to cart.
  - Empty cart display.
  - Add in-stock variant to cart.
  - Variant attributes and formatted prices display.
  - Quantity accumulation on duplicate add.
  - Stock limit validation (reject when exceeding stock, reject when stock is 0).
  - Inactive product rejection.
  - Price injection resistance (ignores client-sent prices).
  - Quantity update with stock bounds.
  - Item removal and cart clear.
  - Inventory remains intact (no decrements during cart operations).
  - Subtotal and header badge verification.

## Verification Plan
1. `php artisan test tests/Feature/CartTest.php` (RED -> GREEN)
2. Full suite: `php artisan test` (92 baseline + new Cart tests PASS, 0 fail)
3. `npm run build` PASS
4. `git diff --check` PASS
5. Commit and safe push to `origin/feature/dang`
