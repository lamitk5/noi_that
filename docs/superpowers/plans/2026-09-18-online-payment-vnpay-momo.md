# Implementation Plan - Phase 5B: Online Payment Gateways (VNPAY Sandbox + MoMo Sandbox)

## 1. Goal
Integrate official VNPAY Sandbox and MoMo Sandbox payment gateways:
- Support payment methods: `vnpay` and `momo` alongside existing `cod` and `bank_transfer`.
- Persist gateway attempts in `payment_transactions` table.
- Re-use Phase 5A inventory reservation: zero double-decrement and zero unexpected stock restoration.
- Authoritative state change exclusively via verified IPN/webhooks (Return URLs are strictly read-only for UX).
- Enforce HMAC signature verification, reference matching, exact amount matching, and idempotent callbacks.
- Enable payment retry for unpaid orders without creating duplicate orders or modifying inventory.

---

## 2. Schema Audit & Migrations
- **Orders Table:** Update `payment_method` column to support `vnpay` and `momo`.
- **Payment Transactions Table (`payment_transactions`):**
  - `id`
  - `order_id`: foreignId constrained to `orders` (cascade delete)
  - `provider`: string (30) - `vnpay`, `momo`
  - `provider_reference`: string (100) - unique per provider (`vnp_TxnRef`, MoMo `orderId`)
  - `request_id`: string (100) nullable - MoMo `requestId`
  - `provider_transaction_id`: string (100) nullable - `vnp_TransactionNo`, MoMo `transId`
  - `amount`: decimal (15, 2)
  - `status`: string (30) - `pending`, `success`, `failed`
  - `response_code`: string (50) nullable
  - `paid_at`: timestamp nullable
  - `failed_at`: timestamp nullable
  - `timestamps`
  - Unique index: `['provider', 'provider_reference']`

---

## 3. Configuration & Environment (.env.example)
- `config/services.php`:
  - `services.vnpay`: `url`, `tmn_code`, `hash_secret`
  - `services.momo`: `url`, `partner_code`, `access_key`, `secret_key`
- `.env.example`:
  - `VNPAY_PAYMENT_URL=https://sandbox.vnpayment.vn/paymentv2/vpcpay.html`
  - `VNPAY_TMN_CODE=`
  - `VNPAY_HASH_SECRET=`
  - `MOMO_PAYMENT_URL=https://test-payment.momo.vn/v2/gateway/api/create`
  - `MOMO_PARTNER_CODE=`
  - `MOMO_ACCESS_KEY=`
  - `MOMO_SECRET_KEY=`

---

## 4. Architectural Flows

### A. Checkout Initiation
1. Customer submits checkout form selecting `vnpay` or `momo`.
2. `CheckoutService::checkout()` creates the `Order`, snapshots items, decrements stock atomically, and clears session cart (from Phase 5A).
3. If `cod` or `bank_transfer`: redirects to `route('checkout.success', $order->order_code)`.
4. If `vnpay`: creates `PaymentTransaction` with unique reference, redirects to `route('payments.vnpay.create', $order->order_code)`.
5. If `momo`: creates `PaymentTransaction` with unique reference, redirects to `route('payments.momo.create', $order->order_code)`.

### B. VNPAY Sandbox (2.1.0)
- **URL Builder:**
  - `vnp_Version = 2.1.0`
  - `vnp_Command = pay`
  - `vnp_Amount = $order->total_price * 100` (integer)
  - `vnp_TxnRef = $paymentTransaction->provider_reference`
  - Sorted query parameters hashed with `HMACSHA512` using `vnp_HashSecret`.
- **Return URL (`GET /thanh-toan/vnpay/return`):**
  - Read-only verification for user feedback. Never marks order paid.
- **IPN URL (`GET /api/payment/vnpay/ipn`):**
  - Verifies HMACSHA512 signature using `hash_equals`.
  - Verifies matching order & amount (`vnp_Amount / 100 == $transaction->amount`).
  - Checks idempotency: if already confirmed, returns `{"RspCode":"02","Message":"Order already confirmed"}`.
  - If success (`vnp_ResponseCode == '00'` and `vnp_TransactionStatus == '00'`):
    - Sets `payment_transaction.status = 'success'`, `provider_transaction_id = $vnp_TransactionNo`, `paid_at = now()`.
    - Sets `order.payment_status = 'paid'`.
    - Returns `{"RspCode":"00","Message":"Confirm Success"}`.

### C. MoMo Sandbox (captureWallet)
- **API Call:**
  - POST to MoMo gateway API with `partnerCode`, `requestId`, `amount`, `orderId`, `orderInfo`, `redirectUrl`, `ipnUrl`, `extraData`, `requestType = 'captureWallet'`.
  - Request signature generated with `HMAC-SHA256`.
  - Handles response and redirects user to `payUrl`.
- **Return URL (`GET /thanh-toan/momo/return`):**
  - Read-only verification for user feedback. Never marks order paid.
- **IPN URL (`POST /api/payment/momo/ipn`):**
  - CSRF excluded in `bootstrap/app.php`.
  - Verifies callback signature with `HMAC-SHA256`.
  - Verifies `partnerCode`, `orderId`, `requestId`, and `amount`.
  - Checks idempotency: if already processed, immediately returns HTTP 204.
  - If `resultCode == 0`: sets `status = 'success'`, `order.payment_status = 'paid'`.
  - If `resultCode != 0`: sets `status = 'failed'`, `response_code = $resultCode`.
  - Always returns HTTP 204 No Content.

### D. Retry Payment Flow
- Order Detail page (`/tai-khoan/don-hang/{order:order_code}`):
  - If `in_array($order->payment_method, ['vnpay', 'momo']) && $order->payment_status !== 'paid' && $order->order_status !== 'canceled'`, provides a "Thanh toán lại" button.
  - Creates a fresh `PaymentTransaction` with a new unique `provider_reference`.
  - Keeps the existing `Order` and reserved stock intact.

---

## 5. Verification & TDD Plan
1. `tests/Feature/Payments/VnpayPaymentTest.php`:
   - Owner initiation vs stranger authorization.
   - Paid order blocks new payment initiation.
   - Correct VNPAY URL generation, parameter sorting, HMAC-SHA512 checksum, and amount * 100.
   - Return URL does NOT mark order paid.
   - IPN verifies signature, amount, reference, updates `payment_status = 'paid'`, returns VNPAY json.
   - Invalid signature and wrong amount rejected with appropriate error codes.
   - Duplicate IPN idempotency without duplicate side effects or stock changes.
2. `tests/Feature/Payments/MomoPaymentTest.php`:
   - Owner initiation vs stranger authorization.
   - HTTP payload format and HMAC-SHA256 signature verification.
   - Redirect to `payUrl` and network error handling.
   - Return URL is read-only.
   - IPN validates signature, amount, updates order to paid, returns HTTP 204.
   - Invalid signature and wrong amount rejected.
   - Duplicate IPN idempotency.
   - Retry creates new payment attempt without modifying stock.
3. Full test suite regression (`php artisan test`).
4. Vite build (`npm run build`).
5. Git formatting check (`git diff --check`).
6. Secret scanning check.
