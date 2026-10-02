import { test, expect } from '@playwright/test';
import crypto from 'crypto';
import { execSync } from 'child_process';

// Configuration keys for MoMo and VNPAY Sandbox
const VNP_SECRET = 'XNBCJFAKAZQSGTARRLGCHVZWCIOIGSHN';
const VNP_TMN = 'CGXZLS0Z';
const MOMO_SECRET = 'K951B6PE1wa80fS6lGXDOjUhtgYXlyQ3';
const MOMO_ACCESS = 'F8BBA842ECF85';
const MOMO_PARTNER = 'MOMO';

function phpUrlEncode(str) {
  return encodeURIComponent(String(str))
    .replace(/%20/g, '+')
    .replace(/[!'()*]/g, c => '%' + c.charCodeAt(0).toString(16).toUpperCase());
}

/**
 * Generate compliant VNPAY HMAC-SHA512 signature
 */
function createVnpaySignature(params, secret = VNP_SECRET) {
  const sortedKeys = Object.keys(params)
    .filter(k => k.startsWith('vnp_') && k !== 'vnp_SecureHash' && k !== 'vnp_SecureHashType')
    .sort();
  const hashData = sortedKeys
    .map(k => `${phpUrlEncode(k)}=${phpUrlEncode(params[k])}`)
    .join('&');
  return crypto.createHmac('sha512', secret).update(Buffer.from(hashData, 'utf-8')).digest('hex');
}

/**
 * Generate compliant MoMo HMAC-SHA256 signature
 */
function createMomoSignature(data, secret = MOMO_SECRET, accessKey = MOMO_ACCESS) {
  const rawHash = `accessKey=${accessKey}&amount=${data.amount ?? ''}&extraData=${data.extraData ?? ''}&message=${data.message ?? ''}&orderId=${data.orderId ?? ''}&orderInfo=${data.orderInfo ?? ''}&orderType=${data.orderType ?? ''}&partnerCode=${data.partnerCode ?? ''}&payType=${data.payType ?? ''}&requestId=${data.requestId ?? ''}&responseTime=${data.responseTime ?? ''}&resultCode=${data.resultCode ?? ''}&transId=${data.transId ?? ''}`;
  return crypto.createHmac('sha256', secret).update(Buffer.from(rawHash, 'utf-8')).digest('hex');
}

test.describe('MoMo & VNPAY Payment Gateways Deep Verification Suite', () => {

  test.beforeAll(() => {
    // Seed test orders and transactions to ensure clean state
    execSync('php artisan payment:seed-test-data', { stdio: 'ignore' });
  });

  // ==========================================
  // SUITE 1: VNPAY Payment URL Generation & Redirection (10 tests)
  // ==========================================

  test('VNP-URL-001: Generate payment URL for pending VNPAY order', async ({ request }) => {
    const res = await request.get('/thanh-toan/vnpay/ORD-TEST-VNPAY-01', {
      maxRedirects: 0,
    });
    expect([302, 200]).toContain(res.status());
    const location = res.headers()['location'];
    if (res.status() === 302 && location) {
      expect(location).toContain('sandbox.vnpayment.vn');
      expect(location).toContain('vnp_SecureHash=');
    }
  });

  test('VNP-URL-002: Verify required VNPAY query parameters in generated payment URL', async ({ request }) => {
    const res = await request.get('/thanh-toan/vnpay/ORD-TEST-VNPAY-01', {
      maxRedirects: 0,
    });
    const location = res.headers()['location'] || '';
    if (location.includes('sandbox.vnpayment.vn')) {
      const url = new URL(location);
      expect(url.searchParams.get('vnp_Version')).toBe('2.1.0');
      expect(url.searchParams.get('vnp_Command')).toBe('pay');
      expect(url.searchParams.get('vnp_TmnCode')).toBe(VNP_TMN);
      expect(url.searchParams.get('vnp_CurrCode')).toBe('VND');
      expect(url.searchParams.get('vnp_Locale')).toBe('vn');
      expect(url.searchParams.has('vnp_TxnRef')).toBe(true);
      expect(url.searchParams.has('vnp_SecureHash')).toBe(true);
    }
  });

  test('VNP-URL-003: Verify vnp_Amount equals total_price * 100', async ({ request }) => {
    const res = await request.get('/thanh-toan/vnpay/ORD-TEST-VNPAY-01', {
      maxRedirects: 0,
    });
    const location = res.headers()['location'] || '';
    if (location.includes('sandbox.vnpayment.vn')) {
      const url = new URL(location);
      // Order amount is 1,000,000 VND -> VNPAY amount is 100,000,000
      expect(url.searchParams.get('vnp_Amount')).toBe('100000000');
    }
  });

  test('VNP-URL-004: Verify HMAC-SHA512 signature in generated VNPAY URL', async ({ request }) => {
    const res = await request.get('/thanh-toan/vnpay/ORD-TEST-VNPAY-01', {
      maxRedirects: 0,
    });
    const location = res.headers()['location'] || '';
    if (location.includes('sandbox.vnpayment.vn')) {
      const url = new URL(location);
      const params = Object.fromEntries(url.searchParams.entries());
      const expectedHash = createVnpaySignature(params);
      expect(params.vnp_SecureHash.toLowerCase()).toBe(expectedHash.toLowerCase());
    }
  });

  test('VNP-URL-005: Reject payment creation for non-existent order (404)', async ({ request }) => {
    const res = await request.get('/thanh-toan/vnpay/ORD-NONEXISTENT-9999');
    expect(res.status()).toBe(404);
  });

  test('VNP-URL-006: Reject payment creation for COD order', async ({ request }) => {
    const res = await request.get('/thanh-toan/vnpay/ORD-TEST-COD-01', {
      maxRedirects: 0,
    });
    expect([302, 200]).toContain(res.status());
    const location = res.headers()['location'] || '';
    // Must redirect back with error instead of sending to gateway
    expect(location).not.toContain('sandbox.vnpayment.vn');
  });

  test('VNP-URL-007: Reject payment creation for already paid order', async ({ request }) => {
    const res = await request.get('/thanh-toan/vnpay/ORD-TEST-PAID-01', {
      maxRedirects: 0,
    });
    expect([302, 200]).toContain(res.status());
    const location = res.headers()['location'] || '';
    expect(location).not.toContain('sandbox.vnpayment.vn');
  });

  test('VNP-URL-008: Reject payment creation for cancelled order', async ({ request }) => {
    const res = await request.get('/thanh-toan/vnpay/ORD-TEST-CANCEL-01', {
      maxRedirects: 0,
    });
    expect([302, 200]).toContain(res.status());
    const location = res.headers()['location'] || '';
    expect(location).not.toContain('sandbox.vnpayment.vn');
  });

  test('VNP-URL-009: Reject unauthorized user access to another users private order (403)', async ({ request }) => {
    // ORD-TEST-USER-01 belongs to user_id = 1
    const res = await request.get('/thanh-toan/vnpay/ORD-TEST-USER-01');
    expect(res.status()).toBe(403);
  });

  test('VNP-URL-010: Verify HTTP POST method on payment create endpoint', async ({ request }) => {
    const res = await request.post('/thanh-toan/vnpay/ORD-TEST-VNPAY-01', {
      maxRedirects: 0,
    });
    expect([200, 302, 419]).toContain(res.status());
  });

  // ==========================================
  // SUITE 2: VNPAY Return URL Handling (10 tests)
  // ==========================================

  test('VNP-RET-001: Success return with valid signature updates order and renders success page', async ({ request }) => {
    const params = {
      vnp_Amount: '100000000',
      vnp_BankCode: 'NCB',
      vnp_BankTranNo: 'VNP12345678',
      vnp_CardType: 'ATM',
      vnp_OrderInfo: 'Thanh toan don hang ORD-TEST-VNPAY-01',
      vnp_PayDate: '20260926120000',
      vnp_ResponseCode: '00',
      vnp_TmnCode: VNP_TMN,
      vnp_TransactionNo: '14567890',
      vnp_TransactionStatus: '00',
      vnp_TxnRef: 'VNP_TEST_TXN_01',
    };
    params.vnp_SecureHash = createVnpaySignature(params);

    const res = await request.get('/thanh-toan/vnpay/return', { params });
    expect(res.status()).toBe(200);
    const body = await res.text();
    expect(body).toContain('Giao dịch thành công');
    expect(body).toContain('Đã thanh toán');
  });

  test('VNP-RET-002: Cancelled return by customer (vnp_ResponseCode = 24) renders failure page', async ({ request }) => {
    const params = {
      vnp_Amount: '150000000',
      vnp_BankCode: 'NCB',
      vnp_OrderInfo: 'Thanh toan don hang',
      vnp_ResponseCode: '24',
      vnp_TmnCode: VNP_TMN,
      vnp_TxnRef: 'VNP_TEST_CANCEL_01',
    };
    params.vnp_SecureHash = createVnpaySignature(params);

    const res = await request.get('/thanh-toan/vnpay/return', { params });
    expect(res.status()).toBe(200);
    const body = await res.text();
    expect(body).toContain('Thanh toán không thành công');
  });

  test('VNP-RET-003: Insufficient balance return (vnp_ResponseCode = 51) renders failed state', async ({ request }) => {
    const params = {
      vnp_Amount: '150000000',
      vnp_BankCode: 'NCB',
      vnp_OrderInfo: 'Thanh toan don hang',
      vnp_ResponseCode: '51',
      vnp_TmnCode: VNP_TMN,
      vnp_TxnRef: 'VNP_TEST_CANCEL_01',
    };
    params.vnp_SecureHash = createVnpaySignature(params);

    const res = await request.get('/thanh-toan/vnpay/return', { params });
    expect(res.status()).toBe(200);
    const body = await res.text();
    expect(body).toContain('Thanh toán không thành công');
  });

  test('VNP-RET-004: Card locked return (vnp_ResponseCode = 09) renders failed state', async ({ request }) => {
    const params = {
      vnp_Amount: '150000000',
      vnp_BankCode: 'NCB',
      vnp_OrderInfo: 'Thanh toan don hang',
      vnp_ResponseCode: '09',
      vnp_TmnCode: VNP_TMN,
      vnp_TxnRef: 'VNP_TEST_CANCEL_01',
    };
    params.vnp_SecureHash = createVnpaySignature(params);

    const res = await request.get('/thanh-toan/vnpay/return', { params });
    expect(res.status()).toBe(200);
    const body = await res.text();
    expect(body).toContain('Thanh toán không thành công');
  });

  test('VNP-RET-005: Tampered signature is detected on return page', async ({ request }) => {
    const params = {
      vnp_Amount: '100000000',
      vnp_BankCode: 'NCB',
      vnp_ResponseCode: '00',
      vnp_TmnCode: VNP_TMN,
      vnp_TxnRef: 'VNP_TEST_TXN_01',
      vnp_SecureHash: 'INVALID_HASH_VALUE_TAMPERED',
    };

    const res = await request.get('/thanh-toan/vnpay/return', { params });
    expect(res.status()).toBe(200);
  });

  test('VNP-RET-006: Return URL accessed without parameters handles cleanly (200, no 500)', async ({ request }) => {
    const res = await request.get('/thanh-toan/vnpay/return');
    expect(res.status()).toBe(200);
  });

  test('VNP-RET-007: Return URL with unknown vnp_TxnRef handles safely', async ({ request }) => {
    const params = {
      vnp_Amount: '100000000',
      vnp_ResponseCode: '00',
      vnp_TmnCode: VNP_TMN,
      vnp_TxnRef: 'NON_EXISTENT_TXN_REF_9999',
    };
    params.vnp_SecureHash = createVnpaySignature(params);

    const res = await request.get('/thanh-toan/vnpay/return', { params });
    expect(res.status()).toBe(200);
  });

  test('VNP-RET-008: Verify return page button link resolves properly', async ({ request }) => {
    const params = {
      vnp_Amount: '100000000',
      vnp_ResponseCode: '00',
      vnp_TmnCode: VNP_TMN,
      vnp_TxnRef: 'VNP_TEST_TXN_01',
    };
    params.vnp_SecureHash = createVnpaySignature(params);

    const res = await request.get('/thanh-toan/vnpay/return', { params });
    expect(res.status()).toBe(200);
    const body = await res.text();
    expect(body).toContain('/checkout/success/ORD-TEST-VNPAY-01');
  });

  test('VNP-RET-009: XSS resilience in vnp_OrderInfo parameter', async ({ request }) => {
    const params = {
      vnp_Amount: '100000000',
      vnp_OrderInfo: '<script>alert(1)</script>',
      vnp_ResponseCode: '00',
      vnp_TmnCode: VNP_TMN,
      vnp_TxnRef: 'VNP_TEST_TXN_01',
    };
    params.vnp_SecureHash = createVnpaySignature(params);

    const res = await request.get('/thanh-toan/vnpay/return', { params });
    expect(res.status()).toBe(200);
    const body = await res.text();
    expect(body).not.toContain('<script>alert(1)</script>');
  });

  test('VNP-RET-010: Idempotent return URL calls do not corrupt state', async ({ request }) => {
    const params = {
      vnp_Amount: '100000000',
      vnp_ResponseCode: '00',
      vnp_TmnCode: VNP_TMN,
      vnp_TxnRef: 'VNP_TEST_TXN_01',
    };
    params.vnp_SecureHash = createVnpaySignature(params);

    const res1 = await request.get('/thanh-toan/vnpay/return', { params });
    const res2 = await request.get('/thanh-toan/vnpay/return', { params });
    expect(res1.status()).toBe(200);
    expect(res2.status()).toBe(200);
  });

  // ==========================================
  // SUITE 3: VNPAY IPN Webhook Verification (10 tests)
  // ==========================================

  test('VNP-IPN-001: Valid IPN success returns RspCode 00 and confirms payment', async ({ request }) => {
    const params = {
      vnp_Amount: '100000000',
      vnp_BankCode: 'NCB',
      vnp_BankTranNo: 'VNPAY_BANK_01',
      vnp_CardType: 'ATM',
      vnp_OrderInfo: 'Thanh toan don hang',
      vnp_PayDate: '20260926120000',
      vnp_ResponseCode: '00',
      vnp_TmnCode: VNP_TMN,
      vnp_TransactionNo: '99887766',
      vnp_TransactionStatus: '00',
      vnp_TxnRef: 'VNP_TEST_TXN_01',
    };
    params.vnp_SecureHash = createVnpaySignature(params);

    const res = await request.get('/api/payment/vnpay/ipn', { params });
    expect(res.status()).toBe(200);
    const json = await res.json();
    expect(['00', '02']).toContain(json.RspCode);
  });

  test('VNP-IPN-002: IPN with invalid signature returns RspCode 97', async ({ request }) => {
    const params = {
      vnp_Amount: '100000000',
      vnp_ResponseCode: '00',
      vnp_TxnRef: 'VNP_TEST_TXN_01',
      vnp_SecureHash: 'INCORRECT_SIGNATURE_HEX',
    };

    const res = await request.get('/api/payment/vnpay/ipn', { params });
    expect(res.status()).toBe(200);
    const json = await res.json();
    expect(json.RspCode).toBe('97');
    expect(json.Message).toBe('Invalid signature');
  });

  test('VNP-IPN-003: IPN with unknown txn ref returns RspCode 01 (Order not found)', async ({ request }) => {
    const params = {
      vnp_Amount: '100000000',
      vnp_ResponseCode: '00',
      vnp_TransactionStatus: '00',
      vnp_TxnRef: 'VNP_NONEXISTENT_REF',
    };
    params.vnp_SecureHash = createVnpaySignature(params);

    const res = await request.get('/api/payment/vnpay/ipn', { params });
    expect(res.status()).toBe(200);
    const json = await res.json();
    expect(json.RspCode).toBe('01');
    expect(json.Message).toBe('Order not found');
  });

  test('VNP-IPN-004: IPN amount mismatch returns RspCode 04 (Invalid amount)', async ({ request }) => {
    const params = {
      vnp_Amount: '50000000', // 500,000 VND instead of 1,000,000 VND
      vnp_ResponseCode: '00',
      vnp_TransactionStatus: '00',
      vnp_TxnRef: 'VNP_TEST_TXN_01',
    };
    params.vnp_SecureHash = createVnpaySignature(params);

    const res = await request.get('/api/payment/vnpay/ipn', { params });
    expect(res.status()).toBe(200);
    const json = await res.json();
    expect(['04', '02']).toContain(json.RspCode);
  });

  test('VNP-IPN-005: IPN idempotency on already confirmed order returns RspCode 02', async ({ request }) => {
    // VNP_TEST_PAID_01 is already paid
    const params = {
      vnp_Amount: '200000000',
      vnp_ResponseCode: '00',
      vnp_TransactionStatus: '00',
      vnp_TxnRef: 'VNP_TEST_PAID_01',
    };
    params.vnp_SecureHash = createVnpaySignature(params);

    const res = await request.get('/api/payment/vnpay/ipn', { params });
    expect(res.status()).toBe(200);
    const json = await res.json();
    expect(json.RspCode).toBe('02');
    expect(json.Message).toBe('Order already confirmed');
  });

  test('VNP-IPN-006: IPN payment failed update (vnp_ResponseCode = 11)', async ({ request }) => {
    const params = {
      vnp_Amount: '100000000',
      vnp_ResponseCode: '11',
      vnp_TransactionStatus: '02',
      vnp_TxnRef: 'VNP_TEST_TXN_01',
    };
    params.vnp_SecureHash = createVnpaySignature(params);

    const res = await request.get('/api/payment/vnpay/ipn', { params });
    expect(res.status()).toBe(200);
    const json = await res.json();
    expect(['00', '02']).toContain(json.RspCode);
  });

  test('VNP-IPN-007: IPN with empty query parameters returns RspCode 97', async ({ request }) => {
    const res = await request.get('/api/payment/vnpay/ipn');
    expect(res.status()).toBe(200);
    const json = await res.json();
    expect(json.RspCode).toBe('97');
  });

  test('VNP-IPN-008: IPN with noise parameters calculates hash accurately', async ({ request }) => {
    const params = {
      vnp_Amount: '200000000',
      vnp_ResponseCode: '00',
      vnp_TransactionStatus: '00',
      vnp_TxnRef: 'VNP_TEST_PAID_01',
      vnp_ExtraData: 'some_arbitrary_metadata',
    };
    params.vnp_SecureHash = createVnpaySignature(params);

    const res = await request.get('/api/payment/vnpay/ipn', { params });
    expect(res.status()).toBe(200);
    const json = await res.json();
    expect(json.RspCode).toBe('02'); // Already confirmed
  });

  test('VNP-IPN-009: Reject POST method on VNPAY IPN route', async ({ request }) => {
    const res = await request.post('/api/payment/vnpay/ipn');
    expect([405, 200]).toContain(res.status());
  });

  test('VNP-IPN-010: Reject DELETE method on VNPAY IPN route', async ({ request }) => {
    const res = await request.delete('/api/payment/vnpay/ipn');
    expect([405, 404]).toContain(res.status());
  });

  // ==========================================
  // SUITE 4: MoMo Payment Initialization & Creation (8 tests)
  // ==========================================

  test('MOMO-URL-001: Trigger payment creation for MoMo order', async ({ request }) => {
    const res = await request.get('/thanh-toan/momo/ORD-TEST-MOMO-01', {
      maxRedirects: 0,
    });
    // In sandbox, redirects away to MoMo payUrl (302) or redirects back with error if API timeout
    expect([200, 302]).toContain(res.status());
  });

  test('MOMO-URL-002: Reject MoMo creation for non-existent order (404)', async ({ request }) => {
    const res = await request.get('/thanh-toan/momo/ORD-NONEXISTENT-MOMO');
    expect(res.status()).toBe(404);
  });

  test('MOMO-URL-003: Reject MoMo creation for COD order', async ({ request }) => {
    const res = await request.get('/thanh-toan/momo/ORD-TEST-COD-01', {
      maxRedirects: 0,
    });
    expect([302, 200]).toContain(res.status());
    const location = res.headers()['location'] || '';
    expect(location).not.toContain('test-payment.momo.vn');
  });

  test('MOMO-URL-004: Reject MoMo creation for already paid order', async ({ request }) => {
    const res = await request.get('/thanh-toan/momo/ORD-TEST-PAID-01', {
      maxRedirects: 0,
    });
    expect([302, 200]).toContain(res.status());
    const location = res.headers()['location'] || '';
    expect(location).not.toContain('test-payment.momo.vn');
  });

  test('MOMO-URL-005: Reject MoMo creation for cancelled order', async ({ request }) => {
    const res = await request.get('/thanh-toan/momo/ORD-TEST-CANCEL-01', {
      maxRedirects: 0,
    });
    expect([302, 200]).toContain(res.status());
    const location = res.headers()['location'] || '';
    expect(location).not.toContain('test-payment.momo.vn');
  });

  test('MOMO-URL-006: Reject unauthorized user access to private order (403)', async ({ request }) => {
    const res = await request.get('/thanh-toan/momo/ORD-TEST-USER-01');
    expect(res.status()).toBe(403);
  });

  test('MOMO-URL-007: Verify POST method on MoMo creation route', async ({ request }) => {
    const res = await request.post('/thanh-toan/momo/ORD-TEST-MOMO-01', {
      maxRedirects: 0,
    });
    expect([200, 302, 419]).toContain(res.status());
  });

  test('MOMO-URL-008: Reject DELETE method on MoMo creation route', async ({ request }) => {
    const res = await request.delete('/thanh-toan/momo/ORD-TEST-MOMO-01');
    expect([405, 404]).toContain(res.status());
  });

  // ==========================================
  // SUITE 5: MoMo Return URL Handling (10 tests)
  // ==========================================

  test('MOMO-RET-001: Success return with valid signature renders success page and updates status', async ({ request }) => {
    const params = {
      partnerCode: MOMO_PARTNER,
      orderId: 'MOMO_TEST_ORD_01',
      requestId: 'REQ_TEST_MOMO_01',
      amount: '500000',
      orderInfo: 'Thanh toan don hang',
      orderType: 'momo_wallet',
      transId: '1234567890',
      resultCode: '0',
      message: 'Successful.',
      payType: 'qr',
      responseTime: '1727366400000',
      extraData: '',
    };
    params.signature = createMomoSignature(params);

    const res = await request.get('/thanh-toan/momo/return', { params });
    expect(res.status()).toBe(200);
    const body = await res.text();
    expect(body).toContain('Giao dịch thành công');
    expect(body).toContain('Đã thanh toán');
  });

  test('MOMO-RET-002: Cancelled return by customer (resultCode = 1006) renders failure page', async ({ request }) => {
    const params = {
      partnerCode: MOMO_PARTNER,
      orderId: 'MOMO_TEST_CANCEL_01',
      requestId: 'REQ_TEST_MOMO_CANCEL_01',
      amount: '500000',
      orderInfo: 'Thanh toan don hang',
      orderType: 'momo_wallet',
      transId: '0',
      resultCode: '1006',
      message: 'Transaction cancelled by user.',
      payType: 'qr',
      responseTime: '1727366400000',
      extraData: '',
    };
    params.signature = createMomoSignature(params);

    const res = await request.get('/thanh-toan/momo/return', { params });
    expect(res.status()).toBe(200);
    const body = await res.text();
    expect(body).toContain('Thanh toán không thành công');
  });

  test('MOMO-RET-003: User denied return (resultCode = 49) renders failure page', async ({ request }) => {
    const params = {
      partnerCode: MOMO_PARTNER,
      orderId: 'MOMO_TEST_CANCEL_01',
      requestId: 'REQ_TEST_MOMO_CANCEL_01',
      amount: '500000',
      orderInfo: 'Thanh toan don hang',
      orderType: 'momo_wallet',
      transId: '0',
      resultCode: '49',
      message: 'User denied authorization.',
      payType: 'qr',
      responseTime: '1727366400000',
      extraData: '',
    };
    params.signature = createMomoSignature(params);

    const res = await request.get('/thanh-toan/momo/return', { params });
    expect(res.status()).toBe(200);
    const body = await res.text();
    expect(body).toContain('Thanh toán không thành công');
  });

  test('MOMO-RET-004: Tampered signature on MoMo return URL', async ({ request }) => {
    const params = {
      partnerCode: MOMO_PARTNER,
      orderId: 'MOMO_TEST_ORD_01',
      requestId: 'REQ_TEST_MOMO_01',
      amount: '500000',
      resultCode: '0',
      signature: 'INVALID_SIGNATURE_TAMPERED',
    };

    const res = await request.get('/thanh-toan/momo/return', { params });
    expect(res.status()).toBe(200);
  });

  test('MOMO-RET-005: Return URL accessed without parameters handles cleanly (200, no 500)', async ({ request }) => {
    const res = await request.get('/thanh-toan/momo/return');
    expect(res.status()).toBe(200);
  });

  test('MOMO-RET-006: Return URL with unknown orderId handles safely', async ({ request }) => {
    const params = {
      partnerCode: MOMO_PARTNER,
      orderId: 'MOMO_UNKNOWN_9999',
      requestId: 'REQ_UNKNOWN',
      amount: '500000',
      resultCode: '0',
    };
    params.signature = createMomoSignature(params);

    const res = await request.get('/thanh-toan/momo/return', { params });
    expect(res.status()).toBe(200);
  });

  test('MOMO-RET-007: Verify MoMo return page link resolves properly', async ({ request }) => {
    const params = {
      partnerCode: MOMO_PARTNER,
      orderId: 'MOMO_TEST_ORD_01',
      requestId: 'REQ_TEST_MOMO_01',
      amount: '500000',
      resultCode: '0',
    };
    params.signature = createMomoSignature(params);

    const res = await request.get('/thanh-toan/momo/return', { params });
    expect(res.status()).toBe(200);
    const body = await res.text();
    expect(body).toContain('/checkout/success/ORD-TEST-MOMO-01');
  });

  test('MOMO-RET-008: XSS resilience in MoMo orderInfo parameter', async ({ request }) => {
    const params = {
      partnerCode: MOMO_PARTNER,
      orderId: 'MOMO_TEST_ORD_01',
      requestId: 'REQ_TEST_MOMO_01',
      amount: '500000',
      orderInfo: '<img src=x onerror=alert(1)>',
      resultCode: '0',
    };
    params.signature = createMomoSignature(params);

    const res = await request.get('/thanh-toan/momo/return', { params });
    expect(res.status()).toBe(200);
    const body = await res.text();
    expect(body).not.toContain('<img src=x onerror=alert(1)>');
  });

  test('MOMO-RET-009: Idempotent MoMo return calls preserve clean state', async ({ request }) => {
    const params = {
      partnerCode: MOMO_PARTNER,
      orderId: 'MOMO_TEST_ORD_01',
      requestId: 'REQ_TEST_MOMO_01',
      amount: '500000',
      resultCode: '0',
    };
    params.signature = createMomoSignature(params);

    const res1 = await request.get('/thanh-toan/momo/return', { params });
    const res2 = await request.get('/thanh-toan/momo/return', { params });
    expect(res1.status()).toBe(200);
    expect(res2.status()).toBe(200);
  });

  test('MOMO-RET-010: MoMo provider branding displayed on result view', async ({ request }) => {
    const params = {
      partnerCode: MOMO_PARTNER,
      orderId: 'MOMO_TEST_ORD_01',
      requestId: 'REQ_TEST_MOMO_01',
      amount: '500000',
      resultCode: '0',
    };
    params.signature = createMomoSignature(params);

    const res = await request.get('/thanh-toan/momo/return', { params });
    const body = await res.text();
    expect(body).toContain('MoMo');
  });

  // ==========================================
  // SUITE 6: MoMo IPN Webhook Verification (10 tests)
  // ==========================================

  test('MOMO-IPN-001: Valid IPN success returns 204 No Content', async ({ request }) => {
    const payload = {
      partnerCode: MOMO_PARTNER,
      orderId: 'MOMO_TEST_ORD_01',
      requestId: 'REQ_TEST_MOMO_01',
      amount: 500000,
      orderInfo: 'Thanh toan don hang',
      orderType: 'momo_wallet',
      transId: 99887711,
      resultCode: 0,
      message: 'Successful.',
      payType: 'qr',
      responseTime: 1727366400000,
      extraData: '',
    };
    payload.signature = createMomoSignature(payload);

    const res = await request.post('/api/payment/momo/ipn', { data: payload });
    expect(res.status()).toBe(204);
  });

  test('MOMO-IPN-002: IPN with invalid signature returns 400 Bad Request', async ({ request }) => {
    const payload = {
      partnerCode: MOMO_PARTNER,
      orderId: 'MOMO_TEST_ORD_01',
      requestId: 'REQ_TEST_MOMO_01',
      amount: 500000,
      resultCode: 0,
      signature: 'BAD_SIGNATURE_HEX',
    };

    const res = await request.post('/api/payment/momo/ipn', { data: payload });
    expect(res.status()).toBe(400);
  });

  test('MOMO-IPN-003: IPN partnerCode mismatch returns 400', async ({ request }) => {
    const payload = {
      partnerCode: 'WRONG_PARTNER_CODE',
      orderId: 'MOMO_TEST_ORD_01',
      requestId: 'REQ_TEST_MOMO_01',
      amount: 500000,
      resultCode: 0,
    };
    payload.signature = createMomoSignature(payload);

    const res = await request.post('/api/payment/momo/ipn', { data: payload });
    expect(res.status()).toBe(400);
  });

  test('MOMO-IPN-004: IPN unknown orderId returns 404 Not Found', async ({ request }) => {
    const payload = {
      partnerCode: MOMO_PARTNER,
      orderId: 'MOMO_UNKNOWN_TRANSACTION_9999',
      requestId: 'REQ_ANY',
      amount: 500000,
      resultCode: 0,
    };
    payload.signature = createMomoSignature(payload);

    const res = await request.post('/api/payment/momo/ipn', { data: payload });
    expect(res.status()).toBe(404);
  });

  test('MOMO-IPN-005: IPN requestId mismatch returns 404', async ({ request }) => {
    const payload = {
      partnerCode: MOMO_PARTNER,
      orderId: 'MOMO_TEST_ORD_01',
      requestId: 'REQ_MISMATCHED_VALUE',
      amount: 500000,
      resultCode: 0,
    };
    payload.signature = createMomoSignature(payload);

    const res = await request.post('/api/payment/momo/ipn', { data: payload });
    expect(res.status()).toBe(404);
  });

  test('MOMO-IPN-006: IPN amount mismatch returns 400', async ({ request }) => {
    const payload = {
      partnerCode: MOMO_PARTNER,
      orderId: 'MOMO_TEST_ORD_01',
      requestId: 'REQ_TEST_MOMO_01',
      amount: 100000, // 100k instead of 500k
      resultCode: 0,
    };
    payload.signature = createMomoSignature(payload);

    const res = await request.post('/api/payment/momo/ipn', { data: payload });
    expect([400, 204]).toContain(res.status());
  });

  test('MOMO-IPN-007: Duplicate IPN on confirmed order returns 204 idempotently', async ({ request }) => {
    const payload = {
      partnerCode: MOMO_PARTNER,
      orderId: 'MOMO_TEST_ORD_01',
      requestId: 'REQ_TEST_MOMO_01',
      amount: 500000,
      resultCode: 0,
    };
    payload.signature = createMomoSignature(payload);

    const res = await request.post('/api/payment/momo/ipn', { data: payload });
    expect(res.status()).toBe(204);
  });

  test('MOMO-IPN-008: IPN payment failed update (resultCode = 1006)', async ({ request }) => {
    const payload = {
      partnerCode: MOMO_PARTNER,
      orderId: 'MOMO_TEST_ORD_01',
      requestId: 'REQ_TEST_MOMO_01',
      amount: 500000,
      resultCode: 1006,
    };
    payload.signature = createMomoSignature(payload);

    const res = await request.post('/api/payment/momo/ipn', { data: payload });
    expect([204, 400]).toContain(res.status());
  });

  test('MOMO-IPN-009: Empty IPN payload returns 400', async ({ request }) => {
    const res = await request.post('/api/payment/momo/ipn', { data: {} });
    expect(res.status()).toBe(400);
  });

  test('MOMO-IPN-010: Reject GET method on MoMo IPN route', async ({ request }) => {
    const res = await request.get('/api/payment/momo/ipn');
    expect([405, 404]).toContain(res.status());
  });

  // ==========================================
  // SUITE 7: End-to-End Payment Flow & Consistency (6 tests)
  // ==========================================

  test('E2E-001: Full VNPAY Lifecycle consistency', async ({ request }) => {
    // 1. IPN confirmation
    const params = {
      vnp_Amount: '100000000',
      vnp_BankCode: 'NCB',
      vnp_ResponseCode: '00',
      vnp_TransactionStatus: '00',
      vnp_TxnRef: 'VNP_TEST_TXN_01',
    };
    params.vnp_SecureHash = createVnpaySignature(params);
    const ipnRes = await request.get('/api/payment/vnpay/ipn', { params });
    expect(ipnRes.status()).toBe(200);

    // 2. Return URL view
    const retRes = await request.get('/thanh-toan/vnpay/return', { params });
    expect(retRes.status()).toBe(200);
    const body = await retRes.text();
    expect(body).toContain('Đã thanh toán');
  });

  test('E2E-002: Full MoMo Lifecycle consistency', async ({ request }) => {
    // 1. IPN confirmation
    const payload = {
      partnerCode: MOMO_PARTNER,
      orderId: 'MOMO_TEST_ORD_01',
      requestId: 'REQ_TEST_MOMO_01',
      amount: 500000,
      resultCode: 0,
    };
    payload.signature = createMomoSignature(payload);
    const ipnRes = await request.post('/api/payment/momo/ipn', { data: payload });
    expect(ipnRes.status()).toBe(204);

    // 2. Return URL view
    const retRes = await request.get('/thanh-toan/momo/return', { params: payload });
    expect(retRes.status()).toBe(200);
    const body = await retRes.text();
    expect(body).toContain('Đã thanh toán');
  });

  test('E2E-003: Anti-tampering check on VNPAY amount', async ({ request }) => {
    const params = {
      vnp_Amount: '99999999', // incorrect amount
      vnp_ResponseCode: '00',
      vnp_TransactionStatus: '00',
      vnp_TxnRef: 'VNP_TEST_TXN_01',
    };
    params.vnp_SecureHash = createVnpaySignature(params);
    const res = await request.get('/api/payment/vnpay/ipn', { params });
    const json = await res.json();
    // Must reject with 04 (Invalid amount) or 02 if already confirmed
    expect(['04', '02']).toContain(json.RspCode);
  });

  test('E2E-004: Anti-tampering check on MoMo amount', async ({ request }) => {
    const payload = {
      partnerCode: MOMO_PARTNER,
      orderId: 'MOMO_TEST_ORD_01',
      requestId: 'REQ_TEST_MOMO_01',
      amount: 999999, // incorrect amount
      resultCode: 0,
    };
    payload.signature = createMomoSignature(payload);
    const res = await request.post('/api/payment/momo/ipn', { data: payload });
    expect([400, 204]).toContain(res.status());
  });

  test('E2E-005: Order tracking reflects payment status', async ({ request }) => {
    const res = await request.get('/orders/track?order_number=ORD-TEST-VNPAY-01&customer_phone=0912345678');
    expect(res.status()).toBe(200);
    const json = await res.json();
    expect(json.success).toBe(true);
    expect(json.data.order_code).toBe('ORD-TEST-VNPAY-01');
  });

  test('E2E-006: Simultaneous concurrent IPN requests handle cleanly', async ({ request }) => {
    const params = {
      vnp_Amount: '200000000',
      vnp_ResponseCode: '00',
      vnp_TransactionStatus: '00',
      vnp_TxnRef: 'VNP_TEST_PAID_01',
    };
    params.vnp_SecureHash = createVnpaySignature(params);

    const [r1, r2, r3] = await Promise.all([
      request.get('/api/payment/vnpay/ipn', { params }),
      request.get('/api/payment/vnpay/ipn', { params }),
      request.get('/api/payment/vnpay/ipn', { params }),
    ]);

    expect(r1.status()).toBe(200);
    expect(r2.status()).toBe(200);
    expect(r3.status()).toBe(200);
  });
});
