# Implementation Plan - Phase 5A: Checkout, Order, Order Items & Atomic Inventory

## 1. Goal
Implement the Checkout process for authenticated users with session cart data:
- Collect shipping information and choose payment method (`cod`, `bank_transfer`).
- Perform an atomic database transaction with `lockForUpdate()` on product variants.
- Re-validate stock, decrement inventory, compute server-side totals, generate unique order code, and create `Order` and snapshot `OrderItems`.
- Clear cart only upon transaction success, preserving cart on failure/rollback.
- Display a dedicated order confirmation page (`checkout.success`) and support order history/detail viewing.

---

## 2. Schema Audit & Snapshot Report
- **Order Model & Table:**
  - `user_id`: nullable FK to `users` (set strictly to authenticated user's ID).
  - `order_code`: unique string identifier (`ORD-YYYYMMDD-XXXXXX`).
  - `customer_name`: string.
  - `customer_phone`: string.
  - `customer_email`: nullable string.
  - `shipping_address`: text.
  - `note`: nullable text.
  - `total_price`: decimal(15, 2) computed server-side.
  - `shipping_fee`: decimal(15, 2) default 0.
  - `payment_method`: enum `['cod', 'bank_transfer']`.
  - `payment_status`: enum `['pending', 'paid', 'failed']` (default: `'pending'`).
  - `order_status`: enum `['pending', 'confirmed', 'shipping', 'completed', 'canceled']` (default: `'pending'`).
- **OrderItem Model & Table:**
  - `order_id`: FK to `orders`.
  - `product_variant_id`: nullable FK to `product_variants`.
  - `product_name`: string snapshot of product name at purchase.
  - `variant_info`: string snapshot of attributes (color, size, material) at purchase.
  - `quantity`: unsigned integer.
  - `price`: decimal(15, 2) snapshot of unit price at purchase.
- **Migrations Added:** NONE. Existing database schema already contains all necessary columns and relations.

---

## 3. Architecture & Data Flow
1. **Empty Cart Guard:** If cart is empty, accessing `GET /thanh-toan` or `POST /thanh-toan` redirects to `route('cart.index')` with warning message.
2. **Auth Requirement:** Checkout routes (`/thanh-toan`, `/dat-hang-thanh-cong/{order}`) are protected by `auth` middleware. Guests attempting to checkout are redirected to `route('login')`.
3. **Double Submission Protection:** Session idempotency token (`checkout_token`) generated on `GET /thanh-toan` and validated/consumed on `POST /thanh-toan`.
4. **Checkout Service (`App\Services\CheckoutService`):**
   - Encapsulates transactional order creation.
   - Sorts variant IDs alphabetically / numerically to prevent deadlocks.
   - Queries `ProductVariant::query()->whereIn('id', $variantIds)->orderBy('id')->lockForUpdate()->with('product')->get()`.
   - Authoritative verification: active product, variant existence, sufficient stock (`$variant->stock >= $quantity`).
   - Server-side calculation: `unit_price = $variant->price ?? $variant->product->base_price`, `subtotal = sum(unit_price * quantity)`, `total_price = subtotal + shipping_fee`.
   - Atomic decrement: `$variant->decrement('stock', $quantity)`.
   - Failure Rollback: Any stock or validation failure triggers database rollback, leaves database inventory and session cart intact.
   - Success: Post-transaction hook clears session cart (`CartService::clear()`).

---

## 4. Proposed File Changes
- **New Files:**
  - `app/Services/CheckoutService.php`: Core order creation and inventory decrement logic.
  - `app/Http/Controllers/CheckoutController.php`: Handles checkout display, store, and success page.
  - `app/Http/Requests/CheckoutRequest.php`: Form request validation for customer info, payment method, and checkout token.
  - `config/shop.php`: Shipping fee and bank account configuration.
  - `resources/views/checkout/index.blade.php`: 2-column checkout page.
  - `resources/views/checkout/success.blade.php`: Order confirmation page.
  - `resources/views/orders/show.blade.php`: Customer order detail page.
  - `tests/Feature/CheckoutTest.php`: Feature tests for checkout workflow, auth, validation, and order creation.
  - `tests/Feature/CheckoutInventoryTest.php`: Tests for inventory locking, atomic decrement, out-of-stock rollback, and cart preservation.
- **Modified Files:**
  - `routes/web.php`: Add checkout routes and order detail route.
  - `resources/views/cart/index.blade.php`: Connect "Tiến hành thanh toán" CTA to `route('checkout.index')`.
  - `app/Http/Controllers/OrderHistoryController.php`: Add `show` method for `orders.show`.

---

## 5. Verification Plan
- Run TDD test suites:
  - `php artisan test tests/Feature/CheckoutTest.php`
  - `php artisan test tests/Feature/CheckoutInventoryTest.php`
- Run full test suite: `php artisan test` (must achieve >= 130 tests PASS, 0 FAIL).
- Build frontend: `npm run build`.
- Check git formatting: `git diff --check`.
- Commit and safe push to `origin/feature/dang`.
