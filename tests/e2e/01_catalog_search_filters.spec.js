import { test, expect } from '@playwright/test';

test.describe('Catalog, Search & Filter Suite (250 tests)', () => {
  // 1. Category Filtering (30 tests)
  const categories = [
    'phong-khach', 'phong-ngu', 'phong-an', 'phong-lam-viec', 'noi-that-van-phong',
    'sofa-go', 'ban-an', 'ghe-an', 'giuong-ngu', 'tu-quan-ao',
    'ke-tivi', 'ban-tra', 'ban-trang-diem', 'tu-giay', 'ke-sach',
    'den-trang-tri', 'tham-trai-san', 'tranh-treo-tuong', 'guong-dung', 'dong-ho',
    'go-tu-nhien', 'go-cong-nghiep', 'sofa-da', 'sofa-vai', 'ban-lam-viec',
    'ghe-van-phong', 'tu-tai-lieu', 'phu-kien', 'khong-ton-tai-1', 'khong-ton-tai-2'
  ];

  categories.forEach((cat, index) => {
    test(`CAT-${String(index + 1).padStart(3, '0')}: Filter catalog by category "${cat}"`, async ({ request }) => {
      const res = await request.get(`/products?category=${encodeURIComponent(cat)}`);
      expect(res.status()).toBe(200);
      const text = await res.text();
      expect(text.length).toBeGreaterThan(100);
    });
  });

  // 2. Keyword Search (50 tests)
  const searchKeywords = [
    'bàn', 'ghế', 'sofa', 'giường', 'tủ', 'gỗ sồi', 'gỗ óc chó', 'kệ', 'đèn', 'thảm',
    'hiện đại', 'cổ điển', 'tối giản', 'gỗ tự nhiên', 'sofa văng', 'bàn ăn tròn', 'bàn làm việc',
    'ghế xoay', 'tủ áo 4 cánh', 'kệ tivi treo tường', 'bàn trà mặt đá', 'giường 1m8', 'nội thất mộc an',
    'da cao cấp', 'vải nỉ', 'gỗ tần bì', 'gỗ gõ đỏ', 'phòng ngủ master', 'phòng khách đẹp',
    'BAN', 'GHE', 'SOFA', 'GIUONG', 'TU', 'Ban An', 'Ghe Don', 'Giuong Ngu',
    'ban_an', 'ban-an', 'sofa2026', 'mocan123', 'sp001', 'go100', '120cm',
    'chính hãng', 'bảo hành 2 năm', 'cao cấp', 'giá rẻ', 'khuyến mãi', 'combo phòng ngủ'
  ];

  searchKeywords.forEach((kw, index) => {
    test(`SRCH-${String(index + 1).padStart(3, '0')}: Search catalog for keyword "${kw}"`, async ({ request }) => {
      const res = await request.get(`/products?search=${encodeURIComponent(kw)}`);
      expect(res.status()).toBe(200);
      const text = await res.text();
      expect(text).toContain('<!DOCTYPE html');
    });
  });

  // 3. Price Range Filters (40 tests)
  const priceRanges = [
    { min: 0, max: 1000000 }, { min: 1000000, max: 2000000 }, { min: 2000000, max: 5000000 },
    { min: 5000000, max: 10000000 }, { min: 10000000, max: 20000000 }, { min: 20000000, max: 50000000 },
    { min: 500000, max: 1500000 }, { min: 1500000, max: 3500000 }, { min: 3500000, max: 7000000 },
    { min: 7000000, max: 12000000 }, { min: 12000000, max: 18000000 }, { min: 18000000, max: 25000000 },
    { min: 25000000, max: 40000000 }, { min: 40000000, max: 60000000 }, { min: 60000000, max: 100000000 },
    { min: 100000, max: 500000 }, { min: 500000, max: 1000000 }, { min: 2000000, max: 3000000 },
    { min: 3000000, max: 4000000 }, { min: 4000000, max: 5000000 }, { min: 6000000, max: 8000000 },
    { min: 8000000, max: 10000000 }, { min: 10000000, max: 15000000 }, { min: 15000000, max: 20000000 },
    { min: 20000000, max: 30000000 }, { min: 30000000, max: 50000000 }, { min: 50000000, max: 80000000 },
    { min: 0, max: 50000000 }, { min: 1000000, max: 100000000 }, { min: 5000000, max: 50000000 },
    { min: 0, max: 0 }, { min: 5000000, max: 1000000 }, { min: -100, max: 1000000 },
    { min: 0, max: -500 }, { min: 'abc', max: 'xyz' }, { min: 999999999, max: 9999999999 },
    { min: 1000000, max: '' }, { min: '', max: 5000000 }, { min: '', max: '' }, { min: 500000, max: 500000 }
  ];

  priceRanges.forEach((range, index) => {
    test(`PRICE-${String(index + 1).padStart(3, '0')}: Price range filter min=${range.min}&max=${range.max}`, async ({ request }) => {
      const res = await request.get(`/products?min_price=${range.min}&max_price=${range.max}`);
      expect(res.status()).toBe(200);
    });
  });

  // 4. Sorting Options (30 tests)
  const sortOptions = [
    'price_asc', 'price_desc', 'latest', 'oldest', 'name_asc', 'name_desc',
    'popular', 'rating', 'discount', 'featured', 'best_seller', 'az', 'za',
    'high_to_low', 'low_to_high', 'newest', 'default', 'random', 'id_asc', 'id_desc',
    'created_at', 'updated_at', 'stock_desc', 'stock_asc', 'views',
    '1', '0', 'true', 'null', 'invalid_sort_key'
  ];

  sortOptions.forEach((sort, index) => {
    test(`SORT-${String(index + 1).padStart(3, '0')}: Sort catalog by option "${sort}"`, async ({ request }) => {
      const res = await request.get(`/products?sort=${encodeURIComponent(sort)}`);
      expect(res.status()).toBe(200);
    });
  });

  // 5. Pagination & Limits (30 tests)
  const pages = [
    1, 2, 3, 4, 5, 6, 7, 8, 9, 10,
    15, 20, 25, 30, 50, 100, 200, 500, 999, 1000,
    0, -1, -5, 'first', 'last', 'prev', 'next', 'null', '999999', 'page_overflow'
  ];

  pages.forEach((page, index) => {
    test(`PAGE-${String(index + 1).padStart(3, '0')}: Access catalog page parameter "${page}"`, async ({ request }) => {
      const res = await request.get(`/products?page=${encodeURIComponent(page)}`);
      expect(res.status()).toBe(200);
    });
  });

  // 6. Product Detail Slug Routes (40 tests)
  const slugs = [
    'ban-an-go-soi-nga-hien-dai', 'sofa-vang-go-oc-cho-cao-cap', 'giuong-ngu-go-tu-nhien-1m8',
    'tu-quan-ao-4-canh-hien-dai', 'ke-tivi-go-soi-toi-gian', 'ban-tra-mat-da-ceramic',
    'ghe-an-go-cao-su-boc-dem', 'ban-lam-viec-go-cong-nghiep-mdf', 'ke-sach-dung-5-tang',
    'tu-giay-thong-minh-3-tang', 'ban-trang-diem-co-den-led', 'sofa-goc-l-boc-da-microfiber',
    'giuong-ngu-boc-nem-tan-co-dien', 'tu-dau-giuong-go-soi', 'ban-an-thong-minh-gap-gon',
    'ghe-bap-benh-thu-gian', 'ke-ruou-go-tu-nhien', 'ban-console-trang-tri',
    'guong-toan-than-khung-go', 'dong-ho-treo-tuong-go-nghe-thuat',
    'non-existent-product-01', 'non-existent-product-02', 'non-existent-product-03',
    'san-pham-khong-ton-tai', 'invalid-slug-12345', 'product-404-check', 'test-slug-999',
    'slug-with-numbers-123', 'slug-with-hyphens-a-b-c', 'SLUG-UPPERCASE-TEST',
    'slug_underscore_test', 'slug.dot.test', 'slug%20space%20test', 'slug+plus+test',
    'sp-1', 'sp-2', 'sp-3', 'sp-4', 'sp-5', 'sp-final'
  ];

  slugs.forEach((slug, index) => {
    test(`SLUG-${String(index + 1).padStart(3, '0')}: Query product detail slug "${slug}"`, async ({ request }) => {
      const res = await request.get(`/products/${encodeURIComponent(slug)}`);
      // Valid products return 200, non-existent products return 404
      expect([200, 404]).toContain(res.status());
    });
  });

  // 7. Security Sanitation & Injection Fuzzing (30 tests)
  const injections = [
    "' OR '1'='1", "1; DROP TABLE products;--", "admin'--", "1' UNION SELECT 1,2,3--",
    "<script>alert('xss')</script>", "<img src=x onerror=alert(1)>", "javascript:alert(1)",
    "../../../../etc/passwd", "..\\..\\..\\windows\\win.ini", "null", "undefined",
    "NaN", "%00", "%27", "%22", "<b>bold</b>", "{{ 7 * 7 }}", "${7*7}",
    "<svg/onload=alert(1)>", "';WAITFOR DELAY '0:0:5'--", "' OR SLEEP(5)--",
    "1 AND 1=1", "1 AND 1=2", "' OR ''='", "Robert'); DROP TABLE students;--",
    "<?php echo 'hack'; ?>", "\"><script>alert(1)</script>", "`id`", "true", "false"
  ];

  injections.forEach((payload, index) => {
    test(`SEC-${String(index + 1).padStart(3, '0')}: Security input sanitation for payload #${index + 1}`, async ({ request }) => {
      const res = await request.get(`/products?search=${encodeURIComponent(payload)}&category=${encodeURIComponent(payload)}`);
      // Must handle securely without 500 error or crash
      expect([200, 404, 400]).toContain(res.status());
    });
  });
});
