# Mộc An Production-Like Hardening Implementation Plan

> For agentic workers:
> REQUIRED SUB-SKILL: use subagent-driven-development or executing-plans.
> Track work with - [ ] checkboxes.

**Goal:** Transform Mộc An ecommerce into a robust, production-ready system suitable for live operation and realistic demonstration, hardening storefront business logic, SEO, query performance, security policies, operations, and cross-device UX without adding massive external subsystems.  
**Architecture:** Monolithic Laravel 12 MVC with Service Layer, Blade templates, Alpine.js, Tailwind CSS, MySQL 8+, and Vite 7. Authoritative stock remains `product_variants.stock`; financial/analytic calculations remain orders with `completed` + `paid`.  
**Tech Stack:** PHP 8.2+, Laravel 12, Blade, Alpine.js 3, Tailwind CSS 3, MySQL 8, Google Gemini API (REST v1beta).  
**Spec Path:** [`docs/superpowers/specs/2026-09-22-production-like-hardening-design.md`](file:///c:/Users/haida/Downloads/bài tập năm tư/PTHT thương mại điện tử_HẢi/Web nội thất/noi_that_feature_dang/docs/superpowers/specs/2026-09-22-production-like-hardening-design.md)  
**Spec Commit:** `8ddcd08`  
**App Baseline:** `9163aa6`  

---

## Global Constraints
- Preserve Laravel 12 + Blade + Alpine + Tailwind + MySQL architecture without framework rewrite.
- Preserve existing core domain services: `CartService`, `CheckoutService`, `OrderWorkflowService`, `RecommendationService`, `AiAssistantService`.
- `product_variants.stock` is the single authoritative source of inventory.
- Revenue, best-sellers, verified purchase reviews, and loyalty rewards strictly require `order_status = 'completed'` AND `payment_status = 'paid'`.
- Preserve live Google Gemini tool calling orchestration (`GeminiProvider`) including header authentication and `thoughtSignature` round-trip preservation.
- Credentials and API keys must remain strictly in environment variables (`.env`); no secrets in code or repository.
- No destructive migrations (`dropColumn`, `migrate:fresh`, `db:wipe`).
- External infrastructure like Redis, CDN, or multi-queue worker fleets remains optional; database and file drivers must run reliably out of the box.

---

## Review Focus (5 Highest-Risk Conditions)

1. **Concurrent Checkout & Stock Oversell Prevention (Task 2):** High concurrency or rapid double-click during checkout on low-stock items must lock variant rows (`lockForUpdate()`) and enforce checkout idempotency so stock never drops below zero and duplicate orders cannot be created.
2. **Order Lifecycle & Payment Callback Idempotency (Task 3 & Task 11):** Duplicate IPN webhook or repeated customer return from VNPay/MoMo must verify signature, update status idempotently, and never double-credit loyalty points or double-adjust inventory.
3. **Strict Resource Ownership Enforcement (Task 4 & Task 10):** Orders, saved addresses, support tickets, and private customer downloads must strictly forbid access by unauthorized users or guests (`403 Forbidden` / `404 Not Found`), eliminating IDOR vulnerabilities.
4. **SiteSetting & CMS Metadata Cache Invalidation (Task 8):** In-memory and file-based caching of branding, themes, and policy pages must invalidate immediately upon admin mutation across both web requests and AI assistant RAG context.
5. **Zero Search Engine Indexing of Private & Checkout Pages (Task 6):** Customer account pages, checkout journeys, private order tracking URLs, and admin dashboards must render `<meta name="robots" content="noindex, nofollow">` and be disallowed in `/robots.txt`.

---

## Phase 1: Storefront + Business Hardening

### Task 1: Storefront Product, Catalog & Cart Consistency

Files:
- Modify: `app/Services/CartService.php`
- Modify: `app/Http/Controllers/CartController.php`
- Modify: `app/Http/Controllers/ProductController.php`
- Modify: `resources/views/cart/index.blade.php`
- Modify: `resources/views/products/show.blade.php`
- Test: `tests/Feature/Hardening/StorefrontCartHardeningTest.php`

Interfaces:
- Consumes: `ProductVariant`, `Product`, `UserCartItem`, `session`
- Produces: Sanitized cart items, real-time stock validations, disabled out-of-stock CTA, error flash messages

- [ ] write failing test in `tests/Feature/Hardening/StorefrontCartHardeningTest.php` verifying:
  - Inactive products or variants cannot be added to cart.
  - Adding quantity exceeding variant stock returns 422 with localized error.
  - Cart page recalculates and flags items if price or stock changed since addition.
  - Out-of-stock variant disables "Thêm vào giỏ" button on PDP.
- [ ] run targeted test and confirm expected failure: `php artisan test --filter=StorefrontCartHardeningTest`
- [ ] implement minimal change in `CartService::add()` and `CartService::getCart()`:
  - Add active checks: `$variant->product->is_active && $variant->stock > 0`.
  - Validate requested quantity against `$variant->stock`.
  - On cart listing, auto-adjust or flag line items where current variant stock < item quantity.
- [ ] update PDP and cart Blade templates to render empty state, out-of-stock badges, and remove any `href="#"` or dead links.
- [ ] run targeted test: `php artisan test --filter=StorefrontCartHardeningTest`
- [ ] run related regression: `php artisan test --filter=CartTest`
- [ ] git diff --check
- [ ] commit: `git commit -m "fix(cart): harden cart stock and active variant checks"`

---

### Task 2: Checkout Concurrency, Idempotency & Stock Restoration

Files:
- Modify: `app/Services/CheckoutService.php`
- Modify: `app/Services/OrderWorkflowService.php`
- Modify: `app/Http/Controllers/CheckoutController.php`
- Test: `tests/Feature/Hardening/CheckoutConcurrencyHardeningTest.php`

Interfaces:
- Consumes: `CartService`, `CheckoutService::processOrder()`, `OrderWorkflowService::cancelOrder()`
- Produces: Atomic `Order` placement, database row locking, idempotent token check, single stock restoration

- [ ] write failing test in `tests/Feature/Hardening/CheckoutConcurrencyHardeningTest.php` verifying:
  - Concurrent checkout requests for last unit of a variant allow only 1 order to succeed and fail the other with friendly error.
  - Submitting checkout with identical idempotency token twice creates only 1 order and redirects to existing order success page.
  - Cancelling an order restores `product_variants.stock` exactly once, even if cancel endpoint is hit concurrently.
  - **(Review Focus 1)**: Verify row-level lock `lockForUpdate()` prevents inventory from dropping below 0 under race conditions.
- [ ] run targeted test and confirm expected failure: `php artisan test --filter=CheckoutConcurrencyHardeningTest`
- [ ] implement minimal change in `CheckoutService.php`:
  - Wrap order placement in `DB::transaction()`.
  - Apply `ProductVariant::where('id', $variantId)->lockForUpdate()->first()` prior to decrementing stock.
  - Validate checkout idempotency token via cache or order session key.
- [ ] implement minimal change in `OrderWorkflowService::cancelOrder()`:
  - Enforce status transition guard: only cancel if status is `pending` or `confirmed`.
  - Wrap stock restoration in transaction and lock order row.
- [ ] run targeted test: `php artisan test --filter=CheckoutConcurrencyHardeningTest`
- [ ] run related regression: `php artisan test --filter=CheckoutTest`
- [ ] git diff --check
- [ ] commit: `git commit -m "fix(checkout): enforce checkout idempotency and row-level stock locks"`

---

### Task 3: Order Lifecycle Operations & Payment State Consistency

Files:
- Modify: `app/Http/Controllers/OrderHistoryController.php`
- Modify: `app/Http/Controllers/Payments/VnpayController.php`
- Modify: `app/Http/Controllers/Payments/MomoController.php`
- Modify: `resources/views/account/orders/show.blade.php`
- Modify: `resources/views/account/orders/print.blade.php`
- Test: `tests/Feature/Hardening/OrderLifecycleHardeningTest.php`

Interfaces:
- Consumes: `Order`, `PaymentTransaction`, `OrderWorkflowService`, `VnpayService`, `MomoService`
- Produces: Safe order actions (cancel, buy-again, retry-payment, print), idempotent payment callback handling

- [ ] write failing test in `tests/Feature/Hardening/OrderLifecycleHardeningTest.php` verifying:
  - "Buy again" ignores out-of-stock or inactive items and adds only available variants to cart with flash notification.
  - "Retry payment" is only allowed for unpaid orders (`payment_status = 'pending'`).
  - Print invoice page renders cleanly without navigation chrome and formats currency and addresses accurately.
  - **(Review Focus 2)**: Duplicate VNPay/MoMo return request verifies HMAC signature idempotently without re-triggering notifications or stock changes.
- [ ] run targeted test and confirm expected failure: `php artisan test --filter=OrderLifecycleHardeningTest`
- [ ] implement minimal change in `OrderHistoryController.php`:
  - In `buyAgain()`: filter `$order->items` against active products and positive stock before adding to cart.
  - In `retryPayment()`: reject completed or canceled orders.
  - In `print()`: clean view presentation, verified customer address and total calculations.
- [ ] implement idempotency guard in `VnpayController` and `MomoController`:
  - Check if order is already marked `paid` before executing post-payment workflows.
- [ ] run targeted test: `php artisan test --filter=OrderLifecycleHardeningTest`
- [ ] run related regression: `php artisan test --filter=OrderHistoryTest`
- [ ] git diff --check
- [ ] commit: `git commit -m "fix(orders): harden order actions and payment callback idempotency"`

---

### Task 4: Account Saved Addresses, Loyalty & Support Ticket Hardening

Files:
- Modify: `app/Http/Controllers/UserAddressController.php`
- Modify: `app/Http/Controllers/TicketController.php`
- Modify: `app/Http/Controllers/ReviewController.php`
- Modify: `resources/views/account/addresses/index.blade.php`
- Modify: `resources/views/account/tickets/index.blade.php`
- Test: `tests/Feature/Hardening/AccountResourceHardeningTest.php`

Interfaces:
- Consumes: `UserAddress`, `SupportTicket`, `Review`, `User`
- Produces: Strict ownership checks, validated address CRUD, verified purchase review validation, ticket replies

- [ ] write failing test in `tests/Feature/Hardening/AccountResourceHardeningTest.php` verifying:
  - User cannot view, update, delete, or set default on another user's address (`403 Forbidden`).
  - User cannot view or reply to another user's support ticket (`403 Forbidden`).
  - Review submission rejects requests where user has no completed and paid order for that product.
  - **(Review Focus 3)**: Verify IDOR attempts across address, ticket, and review endpoints return strict 403/404 responses.
- [ ] run targeted test and confirm expected failure: `php artisan test --filter=AccountResourceHardeningTest`
- [ ] implement minimal change in `UserAddressController.php`, `TicketController.php`, and `ReviewController.php`:
  - Ensure queries use scoped relations `$request->user()->addresses()->findOrFail($id)`.
  - Validate phone number format and required address components (province, district, ward, detail).
  - Verify purchase history via `Order::where('user_id', $user->id)->where('order_status', Order::STATUS_COMPLETED)->where('payment_status', Order::PAYMENT_PAID)->whereHas('items', fn($q) => $q->where('product_id', $product->id))->exists()`.
- [ ] run targeted test: `php artisan test --filter=AccountResourceHardeningTest`
- [ ] run related regression: `php artisan test --filter=ProductReviewTest`
- [ ] git diff --check
- [ ] commit: `git commit -m "fix(account): enforce strict ownership on addresses, tickets, and reviews"`

---

## Phase 2: SEO + Discoverability

### Task 5: Dynamic Metadata, OpenGraph & JSON-LD Structured Data

Files:
- Create: `resources/views/components/seo-meta.blade.php`
- Modify: `resources/views/layouts/app.blade.php`
- Modify: `resources/views/products/show.blade.php`
- Modify: `resources/views/pages/home.blade.php`
- Modify: `resources/views/posts/show.blade.php`
- Modify: `app/Models/Product.php`
- Test: `tests/Feature/Hardening/SeoStructuredDataTest.php`

Interfaces:
- Consumes: `Product`, `Post`, `CmsPage`, `SiteSetting`
- Produces: Dynamic `<title>`, `<meta name="description">`, `canonical`, OpenGraph/Twitter cards, JSON-LD schemas

- [ ] write failing test in `tests/Feature/Hardening/SeoStructuredDataTest.php` verifying:
  - Product page renders valid JSON-LD `Product` schema with name, sku, price, availability (InStock/OutOfStock), and brand.
  - Product and blog pages render JSON-LD `BreadcrumbList` matching hierarchy.
  - Homepage and public pages render Organization and WebSite schema.
  - Dynamic canonical URL strips tracking query parameters (`utm_*`, `fbclid`).
- [ ] run targeted test and confirm expected failure: `php artisan test --filter=SeoStructuredDataTest`
- [ ] create `resources/views/components/seo-meta.blade.php`:
  - Render title, description, canonical link, OpenGraph tags, and Twitter summary card.
  - Support `@push('schema')` for page-specific JSON-LD blocks.
- [ ] update `layouts/app.blade.php` to include `<x-seo-meta />` in `<head>`.
- [ ] update `products/show.blade.php` to push JSON-LD `Product` schema and `BreadcrumbList`.
- [ ] update `posts/show.blade.php` to push JSON-LD `Article` schema.
- [ ] run targeted test: `php artisan test --filter=SeoStructuredDataTest`
- [ ] git diff --check
- [ ] commit: `git commit -m "feat(seo): implement dynamic meta tags and JSON-LD structured data"`

---

### Task 6: Sitemap, Robots & Noindex Protection

Files:
- Create: `app/Http/Controllers/SeoController.php`
- Modify: `routes/web.php`
- Modify: `resources/views/layouts/app.blade.php`
- Test: `tests/Feature/Hardening/SeoRobotsSitemapTest.php`

Interfaces:
- Consumes: Active `Product`, `Category`, `Post`, `CmsPage`
- Produces: `/sitemap.xml`, `/robots.txt`, noindex meta tags on private routes

- [ ] write failing test in `tests/Feature/Hardening/SeoRobotsSitemapTest.php` verifying:
  - `GET /sitemap.xml` returns 200 with XML content-type containing all active products, categories, posts, and static pages.
  - Inactive products/pages are excluded from `sitemap.xml`.
  - `GET /robots.txt` disallows `/admin/`, `/tai-khoan/`, `/thanh-toan/`, `/gio-hang`, and `/api/`, and points to `sitemap.xml`.
  - **(Review Focus 5)**: Visiting `/admin/*`, `/tai-khoan/*`, `/thanh-toan/*`, `/gio-hang`, or `/dat-hang-thanh-cong/*` outputs `<meta name="robots" content="noindex, nofollow">`.
- [ ] run targeted test and confirm expected failure: `php artisan test --filter=SeoRobotsSitemapTest`
- [ ] create `app/Http/Controllers/SeoController.php`:
  - `sitemap()`: queries active records, generates XML format, caches output for 6 hours.
  - `robots()`: returns plain text robots rules.
- [ ] register routes in `routes/web.php` for `/sitemap.xml` and `/robots.txt`.
- [ ] update `layouts/app.blade.php` to inject `noindex, nofollow` when `request()->is('admin*', 'tai-khoan*', 'thanh-toan*', 'gio-hang*', 'dat-hang-thanh-cong*')`.
- [ ] run targeted test: `php artisan test --filter=SeoRobotsSitemapTest`
- [ ] git diff --check
- [ ] commit: `git commit -m "feat(seo): add dynamic sitemap, robots.txt, and noindex guards"`

---

## Phase 3: Performance + Database/Query/Cache Hardening

### Task 7: Database Composite Indexes & Query Hotspot Optimization

Files:
- Create: `database/migrations/2026_09_23_000001_add_production_performance_indexes.php`
- Modify: `app/Http/Controllers/ProductController.php`
- Modify: `app/Http/Controllers/HomeController.php`
- Modify: `app/Http/Controllers/Admin/DashboardController.php`
- Modify: `app/Http/Controllers/Admin/ReportController.php`
- Test: `tests/Feature/Hardening/DatabasePerformanceIndexTest.php`

Interfaces:
- Consumes: MySQL schema, Eloquent queries
- Produces: Composite indexes, eager loading, zero N+1 queries on catalog/dashboard

- [ ] write failing test in `tests/Feature/Hardening/DatabasePerformanceIndexTest.php` verifying:
  - Migration runs forwards and backwards cleanly without error.
  - Visiting homepage executes <= 8 database queries.
  - Visiting catalog `/san-pham` executes <= 6 database queries without N+1 variant queries.
  - Admin dashboard queries use indexed date/status filters.
- [ ] run targeted test and confirm expected failure: `php artisan test --filter=DatabasePerformanceIndexTest`
- [ ] create migration `database/migrations/2026_09_23_000001_add_production_performance_indexes.php`:
  - `products`: index `['is_active', 'category_id', 'base_price']`, index `['is_active', 'created_at']`.
  - `product_variants`: index `['product_id', 'stock']`, index `['color', 'size']`.
  - `orders`: index `['user_id', 'created_at']`, index `['order_status', 'payment_status', 'created_at']`.
  - `reviews`: index `['product_id', 'rating']`.
- [ ] execute migration: `php artisan migrate`
- [ ] optimize queries in `ProductController@index`: eager load `with(['category', 'primaryImage', 'variants'])`.
- [ ] optimize queries in `HomeController@index` and `Admin\DashboardController@index`.
- [ ] run targeted test: `php artisan test --filter=DatabasePerformanceIndexTest`
- [ ] git diff --check
- [ ] commit: `git commit -m "perf(db): add performance composite indexes and eliminate N+1 queries"`

---

### Task 8: Cache Tiering, Invalidation Hooks & Asset Optimization

Files:
- Create: `app/Services/SiteSettingService.php`
- Modify: `app/Models/SiteSetting.php`
- Modify: `app/Http/Controllers/Admin/SiteSettingController.php`
- Modify: `app/Http/Controllers/Admin/FaqController.php`
- Modify: `app/Http/Controllers/Admin/CmsPageController.php`
- Modify: `resources/views/components/ai-chat-widget.blade.php`
- Test: `tests/Feature/Hardening/CacheInvalidationHardeningTest.php`

Interfaces:
- Consumes: `SiteSetting`, `Faq`, `CmsPage`, `Cache`
- Produces: Cached settings/FAQ/CMS responses, immediate invalidation on admin update, lazy image loading

- [ ] write failing test in `tests/Feature/Hardening/CacheInvalidationHardeningTest.php` verifying:
  - `SiteSetting::get()` reads from cache after initial load.
  - **(Review Focus 4)**: Updating a setting in `Admin\SiteSettingController` immediately clears cache and reflects new value on storefront and in AI prompt.
  - Updating FAQ in `Admin\FaqController` clears FAQ cache immediately.
  - AI chat widget does not load message history payload until user clicks open.
- [ ] run targeted test and confirm expected failure: `php artisan test --filter=CacheInvalidationHardeningTest`
- [ ] create `app/Services/SiteSettingService.php` with cache get/set and invalidation methods.
- [ ] update `SiteSetting::get()` to utilize `SiteSettingService` caching (24h TTL, key `site_settings_all`).
- [ ] add cache clearing hooks in `Admin\SiteSettingController`, `Admin\FaqController`, and `Admin\CmsPageController`.
- [ ] update storefront image tags with `loading="lazy"` and `onerror` fallback placeholder.
- [ ] update `ai-chat-widget.blade.php` to defer history loading until `isOpen === true`.
- [ ] run targeted test: `php artisan test --filter=CacheInvalidationHardeningTest`
- [ ] git diff --check
- [ ] commit: `git commit -m "perf(cache): implement site setting caching, invalidation hooks, and lazy loading"`

---

## Phase 4: Security + Operations Readiness

### Task 9: HTTP Security Headers, CSRF, XSS & Upload Validation

Files:
- Create: `app/Http/Middleware/SecurityHeadersMiddleware.php`
- Modify: `bootstrap/app.php`
- Modify: `app/Http/Requests/ReviewStoreRequest.php` *(or Controller)*
- Modify: `app/Http/Requests/TicketStoreRequest.php` *(or Controller)*
- Test: `tests/Feature/Hardening/SecurityHeadersHardeningTest.php`

Interfaces:
- Consumes: HTTP request/response pipeline
- Produces: Security headers (`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `HSTS`), file upload validation

- [ ] write failing test in `tests/Feature/Hardening/SecurityHeadersHardeningTest.php` verifying:
  - All web responses include `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, and `Referrer-Policy: strict-origin-when-cross-origin`.
  - HSTS header is present when running on HTTPS/production.
  - Review and support ticket attachments reject files with invalid extensions or size > 4MB (`mimes:jpg,jpeg,png,webp|max:4096`).
  - XSS payloads in review comments or contact messages are HTML-escaped.
- [ ] run targeted test and confirm expected failure: `php artisan test --filter=SecurityHeadersHardeningTest`
- [ ] create `app/Http/Middleware/SecurityHeadersMiddleware.php` applying standardized security headers.
- [ ] register `SecurityHeadersMiddleware` in `bootstrap/app.php`.
- [ ] enforce file upload validation rules and file name sanitization with random UUIDs.
- [ ] run targeted test: `php artisan test --filter=SecurityHeadersHardeningTest`
- [ ] git diff --check
- [ ] commit: `git commit -m "sec: add HTTP security headers, upload hardening, and XSS sanitization"`

---

### Task 10: Role Guards, Rate Limiting & Admin Audit Logging

Files:
- Create: `app/Models/AdminAuditLog.php`
- Create: `database/migrations/2026_09_23_000002_create_admin_audit_logs_table.php`
- Modify: `app/Http/Controllers/Admin/OrderController.php`
- Modify: `app/Http/Controllers/Admin/InventoryController.php`
- Modify: `app/Http/Controllers/Admin/ProductController.php`
- Modify: `bootstrap/app.php`
- Test: `tests/Feature/Hardening/AdminAuditLogSecurityTest.php`

Interfaces:
- Consumes: Admin actions (order status update, inventory adjustment, product update)
- Produces: Audit trail records in `admin_audit_logs`, rate limit enforcement on sensitive endpoints

- [ ] write failing test in `tests/Feature/Hardening/AdminAuditLogSecurityTest.php` verifying:
  - Non-admin user accessing `/admin/*` receives immediate 403 Forbidden.
  - Admin changing order status creates an `admin_audit_logs` record with admin ID, action, model, old values, and new values.
  - Admin updating stock creates an audit log entry.
  - Login endpoint throttles after 5 failed attempts within 1 minute.
- [ ] run targeted test and confirm expected failure: `php artisan test --filter=AdminAuditLogSecurityTest`
- [ ] create migration `database/migrations/2026_09_23_000002_create_admin_audit_logs_table.php`:
  - Columns: `id`, `user_id`, `action`, `model_type`, `model_id`, `old_values` (json), `new_values` (json), `ip_address`, `created_at`.
- [ ] create `AdminAuditLog` model.
- [ ] execute migration: `php artisan migrate`.
- [ ] attach audit logging calls to critical admin mutations in `OrderController@updateStatus`, `InventoryController@updateStock`, and `ProductController@update`.
- [ ] configure rate limiters in `bootstrap/app.php` for `login` and `ai_chat`.
- [ ] run targeted test: `php artisan test --filter=AdminAuditLogSecurityTest`
- [ ] git diff --check
- [ ] commit: `git commit -m "sec: add admin audit logging and rate limiting guards"`

---

### Task 11: Operational Readiness, Health Checks & Error Views

Files:
- Create: `app/Http/Controllers/HealthCheckController.php`
- Create: `resources/views/errors/403.blade.php`
- Create: `resources/views/errors/404.blade.php`
- Create: `resources/views/errors/500.blade.php`
- Create: `resources/views/errors/503.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Hardening/OperationalHealthCheckTest.php`

Interfaces:
- Consumes: Database connection check, storage write check, HTTP error responses
- Produces: `/up` and `/health` JSON status, branded production error pages without stack trace leakage

- [ ] write failing test in `tests/Feature/Hardening/OperationalHealthCheckTest.php` verifying:
  - `GET /health` returns HTTP 200 with `{ "status": "healthy", "database": "connected", "storage": "writable" }`.
  - When database is unreachable, `/health` returns HTTP 503 with service degraded status.
  - Custom 403, 404, 500 error pages render Mộc An branding and never leak raw PHP traces or API keys when `APP_DEBUG=false`.
- [ ] run targeted test and confirm expected failure: `php artisan test --filter=OperationalHealthCheckTest`
- [ ] create `app/Http/Controllers/HealthCheckController.php` with database ping and storage write tests.
- [ ] register `GET /health` in `routes/web.php`.
- [ ] create branded error views in `resources/views/errors/`: `403.blade.php`, `404.blade.php`, `500.blade.php`, `503.blade.php`.
- [ ] run targeted test: `php artisan test --filter=OperationalHealthCheckTest`
- [ ] git diff --check
- [ ] commit: `git commit -m "ops: implement health check endpoint and branded production error views"`

---

## Phase 5: Final UI/UX + Full Regression + Release Readiness

### Task 12: Cross-Device UI/UX Polish, Accessibility & Motion

Files:
- Modify: `resources/views/layouts/app.blade.php`
- Modify: `resources/views/layouts/admin.blade.php`
- Modify: `resources/views/components/ai-chat-widget.blade.php`
- Modify: `resources/views/pages/home.blade.php`
- Modify: `resources/views/cart/index.blade.php`
- Test: `tests/Feature/Hardening/UiUxAccessibilityTest.php`

Interfaces:
- Consumes: Tailwind semantic color tokens, Alpine.js theme state, viewport breakpoints
- Produces: Responsive layouts (375px -> 1440px), reduced motion support, ARIA tags, toast alerts

- [ ] write failing test in `tests/Feature/Hardening/UiUxAccessibilityTest.php` verifying:
  - Storefront contains exactly 1 floating assistant launcher.
  - Storefront layout renders semantic tokens for surface, heading, and text.
  - Modals and drawers contain `aria-modal="true"`, `role="dialog"`, and `aria-label`.
  - CSS contains `@media (prefers-reduced-motion: reduce)` rules for animations and transitions.
- [ ] run targeted test and confirm expected failure: `php artisan test --filter=UiUxAccessibilityTest`
- [ ] audit and polish mobile responsiveness at 375px:
  - Ensure table containers have `overflow-x-auto`.
  - Ensure AI chat widget bottom sheet uses `h-[85dvh]` with safe-area padding.
  - Ensure cart checkout button remains accessible and sticky on mobile.
- [ ] verify all 5 themes (`default`, `warm-wood`, `modern-minimal`, `vintage-cozy`, `nordic-clean`) render high-contrast text and legible borders.
- [ ] run targeted test: `php artisan test --filter=UiUxAccessibilityTest`
- [ ] git diff --check
- [ ] commit: `git commit -m "ui: polish cross-device responsiveness, theme tokens, and accessibility"`

---

### Task 13: Full Regression Suite, Code Hygiene & Release Readiness Checklist

Files:
- Create: `docs/runbooks/production-deployment-runbook.md`
- Test: `tests/Feature/*`

Interfaces:
- Consumes: All 337+ automated tests, Vite production build, migration status
- Produces: Zero failures, zero warnings, production deployment runbook

- [ ] run complete automated test suite:
  ```bash
  php artisan test
  ```
  Verify: 100% PASS, 0 FAIL, minimum 350+ assertions.
- [ ] run asset production compilation:
  ```bash
  npm run build
  ```
  Verify: Vite compiles CSS and JS with zero errors.
- [ ] run code hygiene checks:
  ```bash
  git diff --check
  ```
  Verify: Zero whitespace or newline anomalies.
- [ ] verify route uniqueness:
  ```bash
  php artisan route:list
  ```
  Verify: Zero duplicate route definitions.
- [ ] verify migration status:
  ```bash
  php artisan migrate:status
  ```
  Verify: All migrations marked `[Ran]`, zero pending.
- [ ] create `docs/runbooks/production-deployment-runbook.md` detailing:
  - Environment variable setup (`.env.example` reference).
  - Database migration and indexing steps.
  - Cache optimization commands (`config:cache`, `route:cache`, `view:cache`).
  - Health check verification procedure.
  - Rollback procedure.
- [ ] commit: `git commit -m "docs: add production deployment runbook and release readiness checklist"`

---

## Explicit REAL / SANDBOX / MOCK Integration Rules

1. **Google Gemini AI Assistant:**
   - `REAL` mode: Requires live API key in `GEMINI_API_KEY`. Executes multi-turn tool calling with Google's API.
   - `SANDBOX` mode: Uses developer Free-Tier key; throttled via internal exponential backoff.
   - `MOCK` mode: Set via `AI_PROVIDER=mock`. Uses `MockAiProvider` with deterministic fixture responses for CI/testing.
   - **Production Failure Rule:** If live Gemini fails in production, the system MUST NEVER fall back to mock content; it MUST return friendly unavailable notice + FAQ / Support / Hotline links.

2. **VNPay Gateway:**
   - `SANDBOX` mode: Default. Uses `https://sandbox.vnpayment.vn/paymentv2/vpcpay.html` with test TMN and Secret.
   - `REAL` mode: Activated strictly by configuring production credentials in `.env`.
   - `MOCK` mode: Automated unit/feature tests mock HTTP calls via `Http::fake()`.

3. **MoMo Gateway:**
   - `SANDBOX` mode: Uses `https://test-payment.momo.vn/v2/gateway/api/create`.
   - `REAL` mode: Production partner credentials in `.env`.
   - `MOCK` mode: Automated feature tests use mocked responses.

4. **Transactional Email:**
   - `MOCK` mode: `MAIL_MAILER=log` for local and CI test runs.
   - `SANDBOX` mode: Mailtrap / Mailpit for staging verification.
   - `REAL` mode: SMTP / Sendgrid for production delivery. Email exceptions must be caught and logged so checkout flow never crashes.

---

## Risks & Mitigations

| Risk | Impact | Mitigation Strategy |
|---|---|---|
| **Inventory race condition** | High (Overselling stock) | Wrap order checkout in database transaction; apply `lockForUpdate()` on variant rows before stock subtraction. |
| **Search engine indexing of customer data** | High (Privacy leak) | Enforce `noindex, nofollow` on all `/tai-khoan/*`, `/admin/*`, and checkout routes; verify via automated tests. |
| **Stale settings/CMS cache** | Medium (Outdated content) | Hook model saved/deleted events to automatically flush `site_settings_all` and CMS cache keys. |
| **Gemini API rate limiting on Free Tier** | Medium (Chat disruption) | Implement retry loop with exponential backoff; fallback gracefully to customer support links. |
| **N+1 queries on catalog page** | Low (Slow response time) | Enforce eager loading `with(['category', 'primaryImage', 'variants'])` in controllers; verify with query count assertions in tests. |
