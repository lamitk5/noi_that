# Production-Like Hardening Specification — Mộc An Ecommerce
**Document Version:** 1.0.0  
**Date:** 2026-09-22  
**Target Branch:** `feature/dang`  
**Baseline Commit:** `9163aa6`  
**Stack:** Laravel 12, Blade, Alpine.js, Tailwind CSS, MySQL 8+, Vite 7

---

## 1. Verified Current-State Assumptions

An audit of repository baseline `9163aa6` establishes the following architectural truths:
- **Framework & Data Layer:** Laravel 12 application with 30 applied migrations, 337 passing automated tests (1,269 assertions), zero pending migrations, and 154 registered routes.
- **Stock Authority:** Inventory is strictly governed by `product_variants.stock`. The `Product::totalStock()` method calculates total available units dynamically.
- **Financial & Analytic Truth:** Revenue calculations, best-sellers, customer loyalty points, and verified purchase reviews strictly require orders with `order_status = 'completed'` AND `payment_status = 'paid'`.
- **Payment & External Integrations:** VNPay (`VnpayService`) and MoMo (`MomoService`) are configured via sandbox URLs and HMAC hashing. Google Gemini AI assistant is integrated via `GeminiProvider` using header authentication (`x-goog-api-key`) with round-trip function calling and `thoughtSignature` preservation.
- **Existing Core Services:**
  - `App\Services\CartService`: Handles guest (cookie/database) and authenticated cart operations, synchronized via `user_cart_items`.
  - `App\Services\CheckoutService`: Manages voucher validation, loyalty point redemption, and atomic order placement wrapped in database transactions.
  - `App\Services\OrderWorkflowService`: Controls state transitions (`pending` -> `confirmed` -> `packed` -> `shipping` -> `completed` / `canceled`) and stock adjustments.
  - `App\Services\RecommendationService`: Generates category and collaborative recommendations based on past orders and session history.
  - `App\Services\Ai\AiAssistantService`: Orchestrates RAG context retrieval and multi-turn Gemini tool execution with a 5-iteration safeguard.
- **UI Architecture:** Blade views with Alpine.js and semantic CSS tokens configured for 5 distinct themes (`default`, `warm-wood`, `modern-minimal`, `vintage-cozy`, `nordic-clean`). Single floating AI launcher (`Trợ lý Mộc An`).

---

## 2. Goals & Non-Goals

### 2.1 Goals
1. **Storefront Resiliency:** Eliminate all dead links, unhandled null object properties, raw enum/database status leaks, and broken image fallbacks across all customer-facing journeys.
2. **SEO Excellence:** Equip every public page with canonical links, semantic meta tags, OpenGraph data, JSON-LD structured data (Product, BreadcrumbList, Organization, WebSite), a dynamically generated `sitemap.xml`, and an optimized `robots.txt`.
3. **Performance Optimization:** Eliminate N+1 query hotspots, add targeted database indexes on high-frequency filter columns, cache site settings and CMS pages, lazy-load images, and defer heavy AI assets until interaction.
4. **Security Hardening:** Enforce strict parameter validation, CSRF verification, input sanitization, file upload MIME/size restrictions, role-based access control, order ownership guards, rate limiting, and HTTP security response headers.
5. **Operational Reliability:** Formulate clear runbooks, establish structured health checks (`/up` and `/health`), configure graceful mail and AI fallback handling, define maintenance mode behavior, and categorize external integrations into explicit REAL, SANDBOX, or MOCK modes.
6. **Polished UX:** Ensure responsive fidelity across mobile (375px), tablet (768px), laptop (1024px), and desktop (1440px), with consistent loading skeletons, empty states, and accessibility compliance (`prefers-reduced-motion`, ARIA labels).

### 2.2 Non-Goals
- Multi-vendor marketplace or third-party merchant seller portals.
- Enterprise Resource Planning (ERP) or multi-warehouse logistics syncing.
- Complex visual drag-and-drop page builder engines.
- Architecture redesign to microservices, Inertia.js, or frontend SPA frameworks.
- Hard dependency on external caching layers (Redis) or paid Content Delivery Networks (CDN).
- Database wiping or backward-incompatible schema migrations.

---

## 3. High-Level Architecture

The hardened architecture retains Laravel's monolithic MVC pattern while enforcing strict layer boundaries:

```
[ HTTP Requests / Webhooks / API ]
               │
               ▼
   [ Security & Rate Limiting Middleware ]
   (SecurityHeaders, RateLimiter, Ownership Guards)
               │
               ▼
         [ Controllers ]
   (Thin HTTP translation, FormRequest validation)
               │
               ▼
       [ Domain Service Layer ]
  ┌──────────────────┬──────────────────┬──────────────────┐
  │   CartService    │ CheckoutService  │OrderWorkflowSvc  │
  ├──────────────────┼──────────────────┼──────────────────┤
  │RecommendationSvc │ AiAssistantSvc   │ Payment Services │
  └──────────────────┴──────────────────┴──────────────────┘
               │
               ▼
    [ Eloquent Models & Cache Layer ]
   (SiteSettingCache, Eager Loading, Query Scopes)
               │
               ▼
        [ MySQL 8 Database ]
```

---

## 4. Components Affected

| Component Group | Affected Files / Directories | Hardening Scope |
|---|---|---|
| **Storefront Views** | `resources/views/pages/*`, `resources/views/products/*`, `resources/views/cart/*`, `resources/views/checkout/*` | Empty states, loading skeletons, null checks, human-readable labels |
| **SEO & Meta** | `resources/views/layouts/app.blade.php`, `routes/web.php`, `app/Http/Controllers/SeoController.php` *(proposed)* | Dynamic `<head>` tags, OpenGraph, JSON-LD schemas, sitemap, robots |
| **Data & Models** | `database/migrations/*`, `app/Models/*` | Missing indexes, relationships eager-loading defaults, localized status accessors |
| **Services & Caching** | `app/Services/*`, `app/Services/SiteSettingService.php` *(proposed)* | Query optimization, tagged/keyed cache, invalidation events |
| **Security & Middleware** | `bootstrap/app.php`, `app/Http/Middleware/*`, `config/session.php` | CSP/HSTS/X-Frame headers, rate limiters, upload MIME/size validation |
| **Operations** | `config/*`, `routes/web.php`, `app/Http/Controllers/HealthCheckController.php` *(proposed)* | Liveness/readiness endpoint, environment validation, fallback logging |

---

## 5. Schema & Database Index Hardening

No destructive migrations (`dropColumn`, `migrate:fresh`) will be executed. The following non-destructive migration will be added:

### Proposed Migration: `2026_09_23_000001_add_production_performance_indexes.php`
```php
Schema::table('products', function (Blueprint $table) {
    // Accelerates catalog filtering by category, active status, price, and newest sorting
    $table->index(['is_active', 'category_id', 'base_price'], 'idx_products_active_cat_price');
    $table->index(['is_active', 'created_at'], 'idx_products_active_created');
});

Schema::table('product_variants', function (Blueprint $table) {
    // Accelerates variant stock lookup and attribute filtering
    $table->index(['product_id', 'stock'], 'idx_variants_product_stock');
    $table->index(['color', 'size'], 'idx_variants_color_size');
});

Schema::table('orders', function (Blueprint $table) {
    // Accelerates customer order history and analytical revenue reports
    $table->index(['user_id', 'created_at'], 'idx_orders_user_created');
    $table->index(['order_status', 'payment_status', 'created_at'], 'idx_orders_status_revenue');
});

Schema::table('reviews', function (Blueprint $table) {
    // Accelerates product review aggregation and rating averages
    $table->index(['product_id', 'rating'], 'idx_reviews_product_rating');
});
```

---

## 6. SEO Design Specification

### 6.1 Meta Data & Head Automation
A unified Blade component `<x-seo-meta />` in `layouts/app.blade.php`:
- **Title Structure:** `{Page Title} — Mộc An \| Nội Thất Gỗ Tự Nhiên & Đương Đại` (capped at 60 characters).
- **Meta Description:** Page-specific description or `SiteSetting::get('site_description')` (truncated between 140–160 characters).
- **Canonical URL:** Fully qualified URL matching `url()->current()`, stripping tracking queries (`utm_*`, `gclid`).
- **OpenGraph & Twitter Card:**
  - `og:site_name`: "Mộc An"
  - `og:type`: "website" (or "product" on PDP, "article" on Post detail)
  - `og:title`, `og:description`, `og:url`
  - `og:image`: High-res product thumbnail or default fallback `storage/images/mocan-og-banner.jpg` (1200x630).
  - `twitter:card`: "summary_large_image"

### 6.2 Structured Data (JSON-LD)
Rendered inline inside `<script type="application/ld+json">`:
1. **Organization & WebSite Schema (Global):**
   ```json
   {
     "@context": "https://schema.org",
     "@graph": [
       {
         "@type": "Organization",
         "@id": "https://mocan.vn/#organization",
         "name": "Mộc An",
         "url": "https://mocan.vn",
         "logo": "https://mocan.vn/images/logo.png",
         "contactPoint": {
           "@type": "ContactPoint",
           "telephone": "+84-1900-6868",
           "contactType": "customer service"
         }
       },
       {
         "@type": "WebSite",
         "@id": "https://mocan.vn/#website",
         "url": "https://mocan.vn",
         "name": "Mộc An",
         "publisher": { "@id": "https://mocan.vn/#organization" }
       }
     ]
   }
   ```
2. **Product Schema (PDP):** Includes `@type: Product`, `name`, `sku`, `image`, `description`, `brand: Mộc An`, and `offers: Offer` (`price`, `priceCurrency: VND`, `availability: InStock / OutOfStock`).
3. **BreadcrumbList Schema:** Applied across Catalog, Category, PDP, Blog, and CMS pages.

### 6.3 Robots & Sitemap
- **`robots.txt` Route:**
  ```
  User-agent: *
  Allow: /
  Disallow: /admin/
  Disallow: /tai-khoan/
  Disallow: /thanh-toan/
  Disallow: /gio-hang
  Disallow: /api/
  Sitemap: https://mocan.vn/sitemap.xml
  ```
- **`sitemap.xml` Route:** Generated dynamically via cached XML stream containing:
  - Static homepage, about, contact, and policy pages (daily/weekly, priority 0.8–1.0).
  - Active categories (`/san-pham?danh-muc={slug}`, weekly, priority 0.8).
  - Active products (`/san-pham/{slug}`, daily, priority 0.9).
  - Published blog posts (`/tin-tuc/{slug}`, monthly, priority 0.6).
- **Noindex Directives:** Pages matching `/tai-khoan/*`, `/thanh-toan/*`, `/gio-hang`, and `/admin/*` output `<meta name="robots" content="noindex, nofollow">`.

---

## 7. Performance & Caching Strategy

### 7.1 N+1 Query Elimination & Eager Loading
- **Catalog & Home:** Enforce `Product::with(['category', 'primaryImage', 'variants'])`. Avoid executing `totalStock()` inside loops without variant preloading.
- **Order History:** Eager-load `Order::with(['items.product.primaryImage', 'items.variant'])`.
- **Review List:** Preload `Review::with('user')`.
- **Cart Listing:** Preload `UserCartItem::with(['variant.product.primaryImage'])`.

### 7.2 Cache Tiering & Invalidation
- **Site Settings Cache:** All calls to `SiteSetting::get()` retrieve from an in-memory / file cache key `site_settings_all` for 24 hours. Cache is flushed immediately upon save in `Admin\SiteSettingController`.
- **FAQ & CMS Pages Cache:** Cache published FAQ entries and static CMS pages (`faq_active_list`, `cms_page_{slug}`) for 12 hours. Flushed on admin update.
- **No Premature Architecture:** File/Database cache driver handles production traffic smoothly; Redis is optional via config without requiring structural code refactoring.

### 7.3 Asset Optimization
- **Lazy Images:** All product grid and gallery images utilize native HTML `loading="lazy"` and `decoding="async"`.
- **Broken Image Fallback:** Implement `onerror="this.onerror=null;this.src='/images/placeholder-furniture.webp'"` directly in Blade image components.
- **AI Widget Deferral:** JavaScript for the AI assistant is initialized lazily. Heavy chat message history is only requested over HTTP upon user interaction (opening the widget), eliminating initial page load overhead.

---

## 8. Security Hardening Model

### 8.1 HTTP Security Headers
Configured via global middleware in `bootstrap/app.php`:
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `X-XSS-Protection: 1; mode=block`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: camera=(), microphone=(), geolocation=()`
- `Strict-Transport-Security: max-age=31536000; includeSubDomains` (when `APP_ENV=production`)

### 8.2 Authorization & Resource Ownership
- **Order Protection:** Customers can only view, cancel, or reprint orders where `orders.user_id === auth()->id()`. Guests cannot access authenticated orders without a cryptographic token.
- **Address & Ticket Ownership:** Strict verification that `addresses.user_id` and `support_tickets.user_id` belong to the authenticated user.
- **Admin Isolation:** Middleware `EnsureUserIsAdmin` validates `user.role === 'admin'`. Non-admin users attempting to access `/admin/*` receive an immediate `403 Forbidden` response.

### 8.3 Rate Limiting
- **Storefront AI Chat:** `RateLimiter::for('ai_chat', ...)` capped at 30 requests/minute per authenticated user ID or guest session ID.
- **Auth Endpoints:** Login and registration capped at 5 attempts/minute per IP address (`throttle:5,1`).
- **Payment Callbacks:** IPN and return callbacks protected against brute-force verification.

### 8.4 Upload Hardening
- File uploads for review photos or support ticket attachments strictly enforce:
  `mimes:jpg,jpeg,png,webp|max:4096` (4MB). File names sanitized with cryptographic hashes; files stored in isolated non-executable directories.

---

## 9. Operations & Deployment-Readiness

### 9.1 Integration Status Matrix

| Subsystem | REAL Mode | SANDBOX Mode | MOCK Mode | Failover Behavior |
|---|---|---|---|---|
| **Google Gemini AI** | Production API Key, Live LLM | AI Studio Free-Tier Key | `MockAiProvider` (Deterministic) | Fallback to friendly support message + FAQ / Hotline action buttons. Never crashes. |
| **VNPay Gateway** | Live Merchant TMN & Hash Secret | Sandbox URL & Test Cards | Mock Payment Driver | Redirects to order failure page with option to switch to COD. |
| **MoMo Gateway** | Live Partner Code & Secret | MoMo Developer Test Gateway | Mock Payment Driver | Redirects to order failure page with retry option. |
| **Email Service** | SMTP / Sendgrid / Postmark | Mailtrap / Mailpit | `MAIL_MAILER=log` | Failed emails logged without interrupting transaction completion. |

### 9.2 Health Check Endpoint
Implement `/up` and `/health`:
- Verifies Database read/write accessibility.
- Verifies storage directory write permissions.
- Returns JSON `{ "status": "healthy", "timestamp": "...", "version": "1.0.0" }` with HTTP 200.

### 9.3 Environment Configuration Checklist (`.env`)
```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://mocan.vn

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mocan_ecommerce
DB_USERNAME=mocan_user
DB_PASSWORD=SecurePassword

# Cache & Session
CACHE_STORE=file
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true

# AI Assistant
AI_PROVIDER=gemini
AI_MODEL=gemini-3.8-flash
GEMINI_API_KEY=AQ.xxxxxx
AI_TIMEOUT=30

# Payments (Sandbox Default)
VNPAY_PAYMENT_URL=https://sandbox.vnpayment.vn/paymentv2/vpcpay.html
VNPAY_TMN_CODE=TEST
VNPAY_HASH_SECRET=TESTSECRET
```

---

## 10. Storefront & Admin UI/UX Hardening

### 10.1 Breakpoints & Viewport Testing
- **Mobile (375px):** Full-width product cards (single or double column), sticky bottom navigation bar for mobile cart/checkout, bottom-sheet AI assistant widget with safe-area padding.
- **Tablet (768px):** Two/three-column catalog grid, compact filter drawer, modal checkout summaries.
- **Laptop (1024px):** Four-column product grid, sticky sidebar filters, side-drawer cart review.
- **Desktop (1440px):** Max container width `max-w-7xl` (1280px) centered, comprehensive footer columns, floating 420px AI launcher.

### 10.2 State Machine Standards
Every asynchronous component (Cart, Checkout, Search, AI, Wishlist) must implement 4 explicit visual states:
1. **Initial / Idle State:** Clean, actionable layout with sensible defaults.
2. **Loading State:** CSS pulse animation or skeleton cards matching the destination shape. Buttons disable with spinning indicator.
3. **Empty State:** High-quality icon, clear explanation (e.g., *"Giỏ hàng của bạn đang trống"*), and primary CTA linking to relevant catalog items.
4. **Error / Retry State:** Human-friendly message explaining the issue, with a distinct "Thử lại" button.

---

## 11. Implementation Milestones

```
Phase 1: Database & Performance
├── Add production performance indexes migration
├── Eager loading audit on Product, Order, Cart queries
└── Implement SiteSetting & CMS memory/file caching

Phase 2: SEO & Meta Integration
├── Implement dynamic <x-seo-meta> Blade component
├── Add JSON-LD schema generators (Product, Breadcrumbs, Org)
└── Add /sitemap.xml and /robots.txt controllers and routes

Phase 3: Security & Error Hardening
├── Register HTTP security headers middleware
├── Enforce strict order/address resource ownership
├── Add file upload validation and rate limiting
└── Standardize custom 404, 403, and 500 error views

Phase 4: UI/UX & Cross-Device Polish
├── Audit responsive layouts (375px -> 1440px)
├── Standardize loading skeletons and empty states
└── Verify accessibility, theme tokens, and motion preferences

Phase 5: Operational Readiness & Verification
├── Implement /health endpoint
├── Final automated test suite execution (Zero regressions)
└── Production runbook and deployment validation
```

---

## 12. Verification & Acceptance Criteria

- [ ] **Automated Tests:** 100% test pass rate with zero regressions (minimum 337 tests, 1,269+ assertions).
- [ ] **Asset Compilation:** `npm run build` completes successfully with zero Vite bundling errors.
- [ ] **Code Hygiene:** `git diff --check` passes with zero whitespace or line-ending anomalies.
- [ ] **Route Cleanliness:** `php artisan route:list` executes cleanly with zero duplicate route definitions.
- [ ] **Migration Safety:** All migrations execute forwards cleanly; no destructive operations on existing tables.
- [ ] **SEO Verification:** Google Rich Results test validates Product and Breadcrumb schemas without errors.
- [ ] **Security Compliance:** OWASP Top 10 vulnerabilities (SQLi, XSS, CSRF, IDOR) mitigated through strict Laravel patterns.
- [ ] **AI Assistant Integrity:** Live Gemini tool calling preserves `thoughtSignature`, manages token rate limits, and falls back gracefully when external upstream services fail.
