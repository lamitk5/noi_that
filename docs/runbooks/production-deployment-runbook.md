# Mộc An Ecommerce — Production Deployment Runbook & Release Readiness

## 1. Overview & Architecture
Mộc An is a modern furniture ecommerce platform built with:
- **Backend:** Laravel 12 (PHP 8.2+)
- **Database:** MySQL 8.0+ (with InnoDB utf8mb4)
- **Frontend:** Blade Templates, Alpine.js, Tailwind CSS (Vite build)
- **Search & Assistant:** Hybrid RAG + Google Gemini Function Calling
- **Payments:** COD, Direct Bank Transfer, VNPAY, MoMo Payment Gateway

---

## 2. Pre-Deployment Readiness Checklist

- [ ] **Domain & SSL/TLS:** Production domain configured (e.g. `https://mocan.vn`) with valid Let's Encrypt or commercial SSL certificate and HTTP-to-HTTPS redirect.
- [ ] **PHP Environment:**
  - PHP version `>= 8.2`
  - Required extensions: `pdo_mysql`, `mbstring`, `bcmath`, `curl`, `xml`, `gd`/`imagick`, `zip`, `opcache`.
  - `opcache.enable=1`, `opcache.validate_timestamps=0` in production `php.ini`.
- [ ] **Node.js & Build Tools:** Node.js `>= 20.x` and npm for building production assets.
- [ ] **Environment Configuration (`.env`):**
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `APP_KEY` generated via `php artisan key:generate`.
  - `APP_URL=https://mocan.vn`
  - `DB_CONNECTION=mysql` with credentials and composite performance indexes applied.
  - `CACHE_STORE=redis` (or `database`)
  - `SESSION_DRIVER=database` (or `redis`)
  - `QUEUE_CONNECTION=database` (or `redis`)
  - Payment credentials configured: `VNPAY_TMN_CODE`, `VNPAY_HASH_SECRET`, `MOMO_PARTNER_CODE`, `MOMO_ACCESS_KEY`, `MOMO_SECRET_KEY`.
  - AI Assistant configured: `AI_PROVIDER=gemini`, `AI_MODEL=gemini-3.8-flash`, `GEMINI_API_KEY=...`, `AI_TIMEOUT=30`.

---

## 3. Step-by-Step Production Deployment Guide

### Step 3.1: Enable Maintenance Mode
```bash
php artisan down --secret="mocan-deploy-bypass-token" --render="errors.503"
```

### Step 3.2: Pull Code & Install Dependencies
```bash
git fetch origin
git checkout -f feature/dang
git pull origin feature/dang

composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
```

### Step 3.3: Database Migrations (Zero-Downtime Safe)
> **Note:** All migrations use safe `Schema::hasTable` and `Schema::hasColumn` checks.
```bash
php artisan migrate --force
```

### Step 3.4: Cache Configurations, Routes, and Blade Views
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 3.5: Public Storage & Queue Restarts
```bash
php artisan storage:link
php artisan queue:restart
```

### Step 3.6: Disable Maintenance Mode
```bash
php artisan up
```

---

## 4. Post-Deployment Verification & Smoke Tests

1. **Operational Health Check:**
   ```bash
   curl -i https://mocan.vn/health
   # Expected: HTTP 200 OK
   # Content: {"status":"healthy","database":"connected","cache":"connected","storage":"writable",...}
   ```

2. **Security Headers Verification:**
   ```bash
   curl -I https://mocan.vn/
   # Expected headers:
   # X-Frame-Options: SAMEORIGIN
   # X-Content-Type-Options: nosniff
   # Referrer-Policy: strict-origin-when-cross-origin
   # Permissions-Policy: camera=(), microphone=(), geolocation=()
   ```

3. **Robots & Sitemap Verification:**
   ```bash
   curl -I https://mocan.vn/robots.txt
   curl -I https://mocan.vn/sitemap.xml
   ```

4. **Ecommerce Smoke Test:**
   - Add product to cart.
   - Complete checkout with COD payment.
   - Confirm inventory is correctly decremented from `product_variants.stock`.
   - Initiate cancel action in Customer Account to verify stock restoration.

5. **AI Shopping Assistant Smoke Test:**
   - Ask product recommendation: *"Tôi cần tìm mẫu bàn ăn gỗ tự nhiên cho căn hộ nhỏ"*.
   - Confirm Gemini tool calling executes and returns accurate product cards.

---

## 5. Rollback Strategy

If a critical issue occurs during release:

1. **Activate Maintenance Mode:**
   ```bash
   php artisan down --render="errors.503"
   ```
2. **Revert Git to Previous Known-Good Commit:**
   ```bash
   git checkout <PREVIOUS_RELEASE_COMMIT_HASH>
   composer install --no-dev --optimize-autoloader
   npm run build
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
3. **Database Rollback (if applicable):**
   Only rollback non-destructive schema changes if required:
   ```bash
   php artisan migrate:rollback --step=1
   ```
4. **Bring Application Back Online:**
   ```bash
   php artisan up
   ```

---

## 6. Ongoing Maintenance & Monitoring

- **Error Monitoring:** Inspect `storage/logs/laravel.log` or connect external APM (Sentry, Datadog).
- **Admin Audit Trail:** Review table `admin_audit_logs` to audit backoffice modifications and staff actions.
- **Database Backups:** Daily automated mysqldump scheduled via cron:
  ```bash
  mysqldump -u mocan_user -p mocan_production | gzip > /backups/mocan_$(date +\%Y\%m\%d_\%H\%M\%S).sql.gz
  ```
