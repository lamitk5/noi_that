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

- [ ] **Domain & SSL/TLS:** Production domain configured (e.g. `https://mocan.vn`) with valid SSL certificate, HSTS enabled on HTTPS, and HTTP-to-HTTPS redirect.
- [ ] **PHP Environment:**
  - PHP version `>= 8.2`
  - Required extensions: `pdo_mysql`, `mbstring`, `bcmath`, `curl`, `xml`, `gd`/`imagick`, `zip`, `opcache`.
  - Recommended `php.ini` production flags: `opcache.enable=1`, `opcache.validate_timestamps=0`.
- [ ] **Node.js & Build Tools:** Node.js `>= 20.x` and npm for building production assets via Vite.
- [ ] **Environment Configuration (`.env`):**
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `APP_KEY` generated via `php artisan key:generate`.
  - `APP_URL=https://mocan.vn`
  - `DB_CONNECTION=mysql` with credentials and composite performance indexes applied.
  - `CACHE_STORE=redis` (or `database`)
  - `SESSION_DRIVER=database` (or `redis`)
  - `QUEUE_CONNECTION=database` (or `redis` / `sync`)
  - Secrets stored strictly in `.env`, never checked into source control.

---

## 3. Integration States: REAL vs SANDBOX vs MOCK

| Component | Production (REAL) | Staging / Sandbox | Local / Unit Tests |
| :--- | :--- | :--- | :--- |
| **VNPAY** | `https://pay.vnpay.vn/vpcpay.html` + Merchant TMN Code & Hash Secret | `https://sandbox.vnpayment.vn/paymentv2/vpcpay.html` | Mocked in Feature Tests |
| **MoMo** | Production API URL + Production Partner Credentials | `https://test-payment.momo.vn/v2/gateway/api/create` | Mocked in Feature Tests |
| **AI Assistant** | `AI_PROVIDER=gemini`, `AI_MODEL=gemini-3.8-flash`, `GEMINI_API_KEY=<real_key>` | Same as prod or test key | `MockAiProvider` (auto in test env) |

---

## 4. Step-by-Step Production Deployment Guide

### Step 4.1: Enable Maintenance Mode
```bash
php artisan down --secret="mocan-deploy-bypass-token" --render="errors.503"
```

### Step 4.2: Pull Code & Build Assets
```bash
git fetch origin
git checkout -f feature/dang
git pull origin feature/dang

composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
```

### Step 4.3: Database Backup (Crucial Pre-Migration Step)
Always backup the production database before executing schema migrations:
```bash
mysqldump -u mocan_user -p mocan_production | gzip > /backups/pre_deploy_$(date +%Y%m%d_%H%M%S).sql.gz
```

### Step 4.4: Database Migrations (Zero-Downtime Safe)
> **Note:** All migrations use safe `Schema::hasTable`, `Schema::hasColumn`, and `Schema::hasIndex` checks.
```bash
php artisan migrate --force
```

### Step 4.5: Cache Configurations, Routes, and Blade Views
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 4.6: Public Storage & Queue Restarts
```bash
php artisan storage:link

# Only if running asynchronous queue workers (Supervisor / systemd daemon):
if [ "$QUEUE_CONNECTION" != "sync" ]; then
    php artisan queue:restart
fi
```

### Step 4.7: Disable Maintenance Mode
```bash
php artisan up
```

---

## 5. Post-Deployment Verification & Smoke Tests

1. **Operational Health Check:**
   ```bash
   curl -i https://mocan.vn/health
   # Expected: HTTP 200 OK
   # Headers: X-Robots-Tag: noindex, nofollow
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
   # Content-Security-Policy: default-src 'self'; ...
   # Strict-Transport-Security: max-age=31536000; includeSubDomains
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

## 6. Rollback Strategy

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
3. **Database Restore (if schema rollback is required):**
   Restore from the pre-deploy backup taken in Step 4.3:
   ```bash
   gunzip < /backups/pre_deploy_<timestamp>.sql.gz | mysql -u mocan_user -p mocan_production
   ```
4. **Bring Application Back Online:**
   ```bash
   php artisan up
   ```

---

## 7. Ongoing Maintenance & Audit Log Retention

- **Admin Audit Log Retention:**
  Records in `admin_audit_logs` should be retained for 90 days. Schedule a periodic pruning job via cron or database event:
  ```sql
  DELETE FROM admin_audit_logs WHERE created_at < NOW() - INTERVAL 90 DAY;
  ```
- **Error Monitoring:** Inspect `storage/logs/laravel.log` or connect external APM (Sentry, Datadog).
- **Daily Automated Database Backups:**
  ```bash
  mysqldump -u mocan_user -p mocan_production | gzip > /backups/daily_$(date +\%Y\%m\%d_\%H\%M\%S).sql.gz
  ```
