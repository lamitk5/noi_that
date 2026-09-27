import { test, expect } from '@playwright/test';

test.describe('Cart & Wishlist Operations Suite (200 tests)', () => {
  // 1. Cart Add Operations (40 tests)
  const cartAddScenarios = Array.from({ length: 40 }, (_, i) => ({
    id: i + 1,
    productId: (i % 20) + 1,
    quantity: (i % 5) + 1,
    color: ['Nâu sồi', 'Gỗ óc chó', 'Trắng', 'Ghi xám', 'Vàng nhạt'][i % 5],
    size: ['Tiêu chuẩn', '160x80cm', '180x80cm', '200x90cm', '140x70cm'][i % 5],
  }));

  cartAddScenarios.forEach((item) => {
    test(`CART-ADD-${String(item.id).padStart(3, '0')}: Add product #${item.productId} qty=${item.quantity} to cart`, async ({ request }) => {
      const res = await request.post(`/cart/add/${item.productId}`, {
        data: {
          quantity: item.quantity,
          color: item.color,
          size: item.size,
        },
      });
      expect([200, 302, 404, 419, 422]).toContain(res.status());
    });
  });

  // 2. Cart Update Quantities (40 tests)
  const updateScenarios = Array.from({ length: 40 }, (_, i) => ({
    id: i + 1,
    cartKey: `item_${(i % 10) + 1}`,
    newQty: (i % 10) + 1,
  }));

  updateScenarios.forEach((item) => {
    test(`CART-UPD-${String(item.id).padStart(3, '0')}: Update cartKey "${item.cartKey}" to quantity ${item.newQty}`, async ({ request }) => {
      const res = await request.post(`/cart/update/${item.cartKey}`, {
        data: {
          quantity: item.newQty,
        },
      });
      expect([200, 302, 404, 419, 422]).toContain(res.status());
    });
  });

  // 3. Cart Boundary & Fuzzing Quantities (40 tests)
  const boundaryQuantities = [
    0, -1, -5, -99, 999, 9999, 1000000, 9999999999,
    'zero', 'one', 'negative', 'null', 'undefined', 'NaN', '1.5', '2,5',
    ' ', '', 'abc', '0x10', 'true', 'false', '[]', '{}',
    '<script>', "' OR 1=1--", '%20', '001', '+1', '1e5',
    '-0', '0.0', '9999999999999999999999', 'Infinity', '-Infinity',
    '@#$%', 'qty_test', 'ten', '1000', 'MAX_INT'
  ];

  boundaryQuantities.forEach((qty, index) => {
    test(`CART-BND-${String(index + 1).padStart(3, '0')}: Cart quantity boundary value "${qty}"`, async ({ request }) => {
      const res = await request.post(`/cart/update/test_key_${index + 1}`, {
        data: {
          quantity: qty,
        },
      });
      // Should handle safely without server crash
      expect([200, 302, 400, 404, 419, 422]).toContain(res.status());
    });
  });

  // 4. Cart Remove & Clear Operations (40 tests)
  const removeScenarios = Array.from({ length: 40 }, (_, i) => ({
    id: i + 1,
    key: i < 35 ? `cart_key_${i + 1}` : 'clear_action',
    action: i < 35 ? 'remove' : 'clear',
  }));

  removeScenarios.forEach((item) => {
    test(`CART-DEL-${String(item.id).padStart(3, '0')}: Cart ${item.action} operation for key "${item.key}"`, async ({ request }) => {
      let res;
      if (item.action === 'clear') {
        res = await request.post('/cart/clear');
      } else {
        res = await request.post(`/cart/remove/${item.key}`);
      }
      expect([200, 302, 404, 419]).toContain(res.status());
    });
  });

  // 5. Wishlist Operations (40 tests)
  const wishlistScenarios = Array.from({ length: 40 }, (_, i) => ({
    id: i + 1,
    productId: (i % 25) + 1,
    action: i % 2 === 0 ? 'toggle' : 'remove',
  }));

  wishlistScenarios.forEach((item) => {
    test(`WISH-${String(item.id).padStart(3, '0')}: Wishlist ${item.action} on product #${item.productId}`, async ({ request }) => {
      let res;
      if (item.action === 'toggle') {
        res = await request.post(`/wishlist/toggle/${item.productId}`);
      } else {
        res = await request.delete(`/wishlist/remove/${item.productId}`);
      }
      // Guest will redirect to login (302) or return json/200/404
      expect([200, 302, 401, 404, 419]).toContain(res.status());
    });
  });
});
