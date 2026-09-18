# Implementation Plan - Phase 6: Order Lifecycle, Admin Order Management, Cancellation & Best Seller Normalization

## 1. Goal
1. Standardize Order Lifecycle: `pending` -> `confirmed` -> `packed` -> `shipping` -> `completed`, and cancellation (`canceled`).
2. Add `packed` status via portable migration.
3. Build comprehensive Admin Order Management (Index, Search, Filter, Detail, Transition Actions, Bank Transfer Payment Confirmation).
4. Implement atomic, exactly-once stock restoration upon cancellation with concurrency locking (`lockForUpdate()`).
5. Enforce payment-aware guards:
   - Online/Bank Transfer orders cannot be confirmed until `payment_status = 'paid'`.
   - Paid orders or pending online payment attempts cannot be canceled without refund handling.
   - COD automatically marks `payment_status = 'paid'` when order reaches `completed`.
6. Customer order timeline & Vietnamese status badges.
7. Normalize Best Seller logic to count only `completed` + `paid` orders.

---

## 2. Schema Audit & Migration
- **Current `orders.order_status`:** `enum('order_status', ['pending', 'confirmed', 'shipping', 'completed', 'canceled'])`.
- **Migration `2026_09_18_200000_update_orders_order_status_table.php`:**
  - Converts `order_status` to `string(30)->default('pending')` to seamlessly accommodate `packed` and future statuses without fragile enum altering.
- **Centralized Status (`App\Enums\OrderStatus` & `Order` constants):**
  - `pending` -> "Đang xử lý"
  - `confirmed` -> "Đã xác nhận"
  - `packed` -> "Đã đóng gói"
  - `shipping` -> "Đang vận chuyển"
  - `completed` -> "Đã giao"
  - `canceled` -> "Đã hủy"

---

## 3. Workflow & Business Rules (`App\Services\OrderWorkflowService`)
- **Forward Progression:**
  - `pending` -> `confirmed` (Requires: if bank_transfer/vnpay/momo, `payment_status == 'paid'`; COD allowed while pending).
  - `confirmed` -> `packed`.
  - `packed` -> `shipping`.
  - `shipping` -> `completed` (If COD: atomically sets `payment_status = 'paid'`).
- **Cancellation:**
  - Allowed from: `pending`, `confirmed`, `packed`.
  - Forbidden from: `shipping`, `completed`, `canceled`.
  - Guards:
    - Paid bank_transfer/vnpay/momo: blocks cancellation ("Đơn hàng đã thanh toán cần xử lý hoàn tiền trước khi hủy.").
    - Pending online transaction attempt: blocks cancellation ("Đơn hàng đang chờ xác nhận thanh toán.").
  - Stock Restoration:
    - Sorts variant IDs to prevent deadlocks.
    - Locks `ProductVariant` rows with `lockForUpdate()`.
    - Restores stock: `stock += item.quantity`.
    - Exactly once: subsequent calls on already canceled orders are rejected/no-op without duplicate stock increment.
- **Bank Transfer Payment Confirmation:**
  - Action for admin to mark bank transfer orders as `paid` upon bank statement reconciliation.

---

## 4. Best Seller Normalization
- Update `Product::scopeBestSelling()`:
  - Strictly filters orders with `order_status = 'completed'` AND `payment_status = 'paid'`.
  - Excludes `pending`, `confirmed`, `packed`, `shipping`, `canceled`, and `failed`.

---

## 5. Proposed File Changes
- **New Files:**
  - `database/migrations/2026_09_18_200000_update_orders_order_status_table.php`
  - `app/Enums/OrderStatus.php`
  - `app/Services/OrderWorkflowService.php`
  - `app/Http/Controllers/Admin/OrderController.php`
  - `resources/views/admin/orders/index.blade.php`
  - `resources/views/admin/orders/show.blade.php`
  - `tests/Feature/Admin/OrderManagementTest.php`
  - `tests/Feature/Admin/OrderWorkflowTest.php`
  - `tests/Feature/Admin/OrderCancellationTest.php`
- **Modified Files:**
  - `app/Models/Order.php`: Add constants and status label helpers.
  - `app/Models/Product.php`: Update `scopeBestSelling` query.
  - `routes/web.php`: Register admin order routes.
  - `resources/views/admin/dashboard.blade.php`: Connect Orders card link.
  - `resources/views/orders/index.blade.php`: Update status labels.
  - `resources/views/orders/show.blade.php`: Render complete 5-step customer status timeline.
  - `tests/Feature/BestSellerTest.php`: Add tests for status exclusion.

---

## 6. Verification Plan
- Run TDD suites:
  - `php artisan test tests/Feature/Admin/`
  - `php artisan test tests/Feature/BestSellerTest.php`
- Run full suite: `php artisan test` (Zero failures).
- Build frontend: `npm run build`.
- Format check: `git diff --check`.
- Commit and safe push to `origin/feature/dang`.
