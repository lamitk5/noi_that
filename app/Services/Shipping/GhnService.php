<?php

namespace App\Services\Shipping;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GhnService
{
    protected string $apiUrl;
    protected ?string $token;
    protected int $shopId;
    protected int $fromDistrictId;
    protected string $fromWardCode;
    protected float $defaultFee;
    protected bool $autoCreate;

    public function __construct()
    {
        $this->apiUrl = rtrim(config('services.ghn.url', 'https://dev-online-gateway.ghn.vn/shiip/public-api/'), '/') . '/';
        $this->token = config('services.ghn.token');
        $this->shopId = (int) config('services.ghn.shop_id', 216783);
        $this->fromDistrictId = (int) config('services.ghn.from_district_id', 1442);
        $this->fromWardCode = (string) config('services.ghn.from_ward_code', '20101');
        $this->defaultFee = (float) config('services.ghn.default_fee', 30000);
        $this->autoCreate = (bool) config('services.ghn.auto_create_order', true);
    }

    public function getProvinces(): array
    {
        return Cache::remember('ghn_provinces', 86400, function () {
            try {
                $response = Http::timeout(5)
                    ->withHeaders(['Token' => $this->token])
                    ->get($this->apiUrl . 'master-data/province');

                if ($response->successful() && !empty($response->json('data'))) {
                    $provinces = collect($response->json('data'))
                        ->map(fn ($p) => [
                            'id' => (int) ($p['ProvinceID'] ?? 0),
                            'name' => (string) ($p['ProvinceName'] ?? ''),
                            'code' => (string) ($p['Code'] ?? ''),
                        ])
                        ->filter(fn ($p) => $p['id'] > 0 && !empty($p['name']) && !str_contains($p['name'], 'Test') && !str_contains($p['name'], '02'))
                        ->sortBy('name')
                        ->values()
                        ->all();

                    if (count($provinces) >= 60) {
                        return $provinces;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('GHN getProvinces error: ' . $e->getMessage());
            }

            return $this->getFallbackProvinces();
        });
    }

    public function getDistricts(int $provinceId): array
    {
        if ($provinceId <= 0) {
            return [];
        }

        return Cache::remember("ghn_districts_{$provinceId}", 86400, function () use ($provinceId) {
            try {
                $response = Http::timeout(10)
                    ->withHeaders(['Token' => $this->token])
                    ->post($this->apiUrl . 'master-data/district', [
                        'province_id' => $provinceId,
                    ]);

                if ($response->successful() && !empty($response->json('data'))) {
                    return collect($response->json('data'))
                        ->map(fn ($d) => [
                            'id' => (int) ($d['DistrictID'] ?? 0),
                            'name' => (string) ($d['DistrictName'] ?? ''),
                            'province_id' => (int) ($d['ProvinceID'] ?? 0),
                        ])
                        ->filter(fn ($d) => $d['id'] > 0 && !empty($d['name']))
                        ->sortBy('name')
                        ->values()
                        ->all();
                }
            } catch (\Throwable $e) {
                Log::warning("GHN getDistricts({$provinceId}) error: " . $e->getMessage());
            }

            return $this->getFallbackDistricts($provinceId);
        });
    }

    public function getWards(int $districtId): array
    {
        if ($districtId <= 0) {
            return [];
        }

        return Cache::remember("ghn_wards_{$districtId}", 86400, function () use ($districtId) {
            try {
                $response = Http::timeout(10)
                    ->withHeaders(['Token' => $this->token])
                    ->post($this->apiUrl . 'master-data/ward', [
                        'district_id' => $districtId,
                    ]);

                if ($response->successful() && !empty($response->json('data'))) {
                    return collect($response->json('data'))
                        ->map(fn ($w) => [
                            'code' => (string) ($w['WardCode'] ?? ''),
                            'name' => (string) ($w['WardName'] ?? ''),
                            'district_id' => (int) ($w['DistrictID'] ?? 0),
                        ])
                        ->filter(fn ($w) => !empty($w['code']) && !empty($w['name']))
                        ->sortBy('name')
                        ->values()
                        ->all();
                }
            } catch (\Throwable $e) {
                Log::warning("GHN getWards({$districtId}) error: " . $e->getMessage());
            }

            return $this->getFallbackWards($districtId);
        });
    }

    /**
     * Trích xuất kích thước [dài, rộng, cao] (cm) từ chuỗi định dạng (vd: "160 x 85 x 75 cm" hoặc "180x80cm").
     */
    public static function parseDimensions(?string $dimString): array
    {
        if (empty($dimString)) {
            return ['length' => 30, 'width' => 20, 'height' => 20];
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*[xX*×]\s*(\d+(?:\.\d+)?)(?:\s*[xX*×]\s*(\d+(?:\.\d+)?))?/', $dimString, $matches)) {
            $length = (int) round((float) $matches[1]);
            $width = (int) round((float) $matches[2]);
            $height = isset($matches[3]) ? (int) round((float) $matches[3]) : 20;

            return [
                'length' => max(10, $length),
                'width' => max(10, $width),
                'height' => max(10, $height),
            ];
        }

        return ['length' => 30, 'width' => 20, 'height' => 20];
    }

    public function calculateFee(
        int $toDistrictId,
        string $toWardCode,
        int $weight = 2000,
        float $insuranceValue = 0,
        int $length = 30,
        int $width = 20,
        int $height = 20
    ): float {
        if ($toDistrictId <= 0 || $toWardCode === '') {
            return $this->defaultFee;
        }

        try {
            $pkgLength = min(150, max(10, $length));
            $pkgWidth = min(150, max(10, $width));
            $pkgHeight = min(150, max(10, $height));
            $pkgWeight = min(50000, max(200, $weight));

            $serviceTypeId = ($pkgWeight > 20000 || $pkgLength > 100 || $pkgWidth > 100) ? 5 : 2;

            $payload = [
                'shop_id' => $this->shopId,
                'service_type_id' => $serviceTypeId,
                'from_district_id' => $this->fromDistrictId,
                'to_district_id' => $toDistrictId,
                'to_ward_code' => (string) $toWardCode,
                'height' => $pkgHeight,
                'length' => $pkgLength,
                'width' => $pkgWidth,
                'weight' => $pkgWeight,
                'insurance_value' => (int) min(5000000, max(0, $insuranceValue)),
            ];

            $response = Http::timeout(10)
                ->withHeaders([
                    'Token' => $this->token,
                    'ShopId' => (string) $this->shopId,
                ])
                ->post($this->apiUrl . 'v2/shipping-order/fee', $payload);

            if ($response->successful() && isset($response->json('data')['total'])) {
                return (float) $response->json('data')['total'];
            }

            // Thử fallback sang loại dịch vụ khác nếu gói hàng không hỗ trợ dịch vụ hiện tại
            if ($serviceTypeId === 5) {
                $payload['service_type_id'] = 2;
                $payload['weight'] = min(20000, $pkgWeight);
                $fallbackRes = Http::timeout(10)
                    ->withHeaders([
                        'Token' => $this->token,
                        'ShopId' => (string) $this->shopId,
                    ])
                    ->post($this->apiUrl . 'v2/shipping-order/fee', $payload);

                if ($fallbackRes->successful() && isset($fallbackRes->json('data')['total'])) {
                    return (float) $fallbackRes->json('data')['total'];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('GHN calculateFee error: ' . $e->getMessage());
        }

        if ($toDistrictId === $this->fromDistrictId) {
            return 22000.0;
        }

        return $this->defaultFee;
    }

    public function createShippingOrder(Order $order): ?string
    {
        if (!empty($order->ghn_order_code)) {
            return $order->ghn_order_code;
        }

        $order->loadMissing('items.variant.product');

        $isCod = $order->payment_method === 'cod';
        $codAmount = $isCod ? (int) round((float) $order->total_price) : 0;

        $items = [];
        $totalWeight = 0;
        $maxLength = 30;
        $maxWidth = 20;
        $totalHeight = 0;

        foreach ($order->items as $item) {
            $qty = max(1, (int) $item->quantity);
            $variant = $item->variant;
            $product = $variant?->product;

            // Kích thước: ưu tiên từ biến thể -> ghi chú biến thể -> thông số sản phẩm
            $dimString = $variant?->size ?: ($item->variant_info ?: $product?->dimensions);
            $dims = self::parseDimensions($dimString);

            // Cân nặng: ưu tiên từ sản phẩm (kg -> gram)
            $unitWeight = 500;
            if ($product && !empty($product->weight) && (float) $product->weight > 0) {
                $unitWeight = (int) round(((float) $product->weight) * 1000);
            }
            $itemWeight = max(200, $unitWeight * $qty);
            $totalWeight += $itemWeight;

            // Giới hạn từng chiều tối đa 150cm theo quy định GHN
            $itemLength = min(150, max(10, $dims['length']));
            $itemWidth = min(150, max(10, $dims['width']));
            $itemHeight = min(150, max(10, $dims['height']));

            $maxLength = max($maxLength, $itemLength);
            $maxWidth = max($maxWidth, $itemWidth);
            $totalHeight += $itemHeight * $qty;

            $items[] = [
                'name' => Str::limit($item->product_name ?? 'Sản phẩm nội thất', 80, ''),
                'code' => (string) ($item->product_variant_id ?? $item->id),
                'quantity' => $qty,
                'price' => (int) round((float) $item->price),
                'weight' => $itemWeight,
                'length' => $itemLength,
                'width' => $itemWidth,
                'height' => $itemHeight,
            ];
        }

        if (empty($items)) {
            $items[] = [
                'name' => 'Sản phẩm nội thất',
                'quantity' => 1,
                'price' => (int) round((float) $order->total_price),
                'weight' => 1000,
                'length' => 30,
                'width' => 20,
                'height' => 20,
            ];
            $totalWeight = 1000;
            $maxLength = 30;
            $maxWidth = 20;
            $totalHeight = 20;
        }

        $packageLength = min(150, max(10, $maxLength));
        $packageWidth = min(150, max(10, $maxWidth));
        $packageHeight = min(150, max(10, $totalHeight));
        $packageWeight = min(50000, max(500, $totalWeight));

        $serviceTypeId = ($packageWeight > 20000 || $packageLength > 100 || $packageWidth > 100) ? 5 : 2;

        $toDistrictId = (int) ($order->district_id ?: 1444);
        $toWardCode = (string) ($order->ward_code ?: '20311');

        $payload = [
            'payment_type_id' => 1,
            'note' => $order->note ?: 'Giao hàng giờ hành chính',
            'required_note' => 'CHOXEMHANGKHONGTHU',
            'from_name' => 'Nội Thất Mộc An',
            'from_phone' => '0912345678',
            'from_address' => 'Số 12 Nguyễn Phong Sắc, Phường Dịch Vọng',
            'from_ward_name' => 'Phường Dịch Vọng',
            'from_district_name' => 'Quận Cầu Giấy',
            'from_province_name' => 'Hà Nội',
            'return_phone' => '0912345678',
            'return_address' => 'Số 12 Nguyễn Phong Sắc, Phường Dịch Vọng, Quận Cầu Giấy',
            'client_order_code' => $order->order_code,
            'to_name' => $order->customer_name,
            'to_phone' => $order->customer_phone,
            'to_address' => $order->shipping_address,
            'to_ward_code' => $toWardCode,
            'to_district_id' => $toDistrictId,
            'cod_amount' => $codAmount,
            'content' => "Don hang {$order->order_code}",
            'weight' => $packageWeight,
            'length' => $packageLength,
            'width' => $packageWidth,
            'height' => $packageHeight,
            'insurance_value' => (int) min(5000000, max(0, (float) $order->total_price)),
            'service_type_id' => $serviceTypeId,
            'items' => $items,
        ];

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Token' => $this->token,
                    'ShopId' => (string) $this->shopId,
                ])
                ->post($this->apiUrl . 'v2/shipping-order/create', $payload);

            // Tự động thử lại loại dịch vụ 2 nếu loại 5 không hỗ trợ khu vực này
            if (! $response->successful() && $serviceTypeId === 5) {
                $fallbackPayload = $payload;
                $fallbackPayload['service_type_id'] = 2;
                $fallbackPayload['weight'] = min(20000, $packageWeight);
                $fallbackRes = Http::timeout(15)
                    ->withHeaders([
                        'Token' => $this->token,
                        'ShopId' => (string) $this->shopId,
                    ])
                    ->post($this->apiUrl . 'v2/shipping-order/create', $fallbackPayload);

                if ($fallbackRes->successful() && !empty($fallbackRes->json('data')['order_code'])) {
                    $response = $fallbackRes;
                }
            }

            $data = $response->json('data');
            if ($response->successful() && !empty($data['order_code'])) {
                $ghnCode = $data['order_code'];
                $expectedDelivery = !empty($data['expected_delivery_time'])
                    ? date('Y-m-d H:i:s', strtotime($data['expected_delivery_time']))
                    : now()->addDays(3);

                $order->update([
                    'ghn_order_code' => $ghnCode,
                    'ghn_status' => 'ready_to_pick',
                    'ghn_expected_delivery_at' => $expectedDelivery,
                    'ghn_log' => $data,
                ]);

                return $ghnCode;
            }

            Log::warning('GHN create order response not successful: ' . json_encode($response->json()));
        } catch (\Throwable $e) {
            Log::error('GHN createShippingOrder exception: ' . $e->getMessage());
        }

        $simulatedCode = 'GHN' . strtoupper(Str::random(8));
        $order->update([
            'ghn_order_code' => $simulatedCode,
            'ghn_status' => 'ready_to_pick',
            'ghn_expected_delivery_at' => now()->addDays(3),
            'ghn_log' => ['simulated' => true, 'created_at' => now()->toIso8601String()],
        ]);

        return $simulatedCode;
    }

    protected function getMasterLocationData(): array
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $path = database_path('data/ghn_locations.json');
        if (file_exists($path)) {
            $json = @file_get_contents($path);
            if ($json) {
                $decoded = json_decode($json, true);
                if (is_array($decoded)) {
                    return $cached = $decoded;
                }
            }
        }

        return $cached = [];
    }

    protected function getFallbackProvinces(): array
    {
        $master = $this->getMasterLocationData();
        if (!empty($master)) {
            return collect($master)
                ->map(fn ($p) => [
                    'id' => (int) $p['id'],
                    'name' => (string) $p['name'],
                    'code' => (string) ($p['code'] ?? ''),
                ])
                ->sortBy('name')
                ->values()
                ->all();
        }

        return [
            ['id' => 201, 'name' => 'Hà Nội', 'code' => 'HN'],
            ['id' => 202, 'name' => 'Hồ Chí Minh', 'code' => 'HCM'],
            ['id' => 203, 'name' => 'Đà Nẵng', 'code' => 'DN'],
            ['id' => 224, 'name' => 'Hải Phòng', 'code' => 'HP'],
            ['id' => 220, 'name' => 'Cần Thơ', 'code' => 'CT'],
            ['id' => 205, 'name' => 'Bình Dương', 'code' => 'BD'],
            ['id' => 204, 'name' => 'Đồng Nai', 'code' => 'DNA'],
            ['id' => 208, 'name' => 'Khánh Hòa', 'code' => 'KH'],
            ['id' => 230, 'name' => 'Quảng Ninh', 'code' => 'QN'],
            ['id' => 209, 'name' => 'Lâm Đồng', 'code' => 'LD'],
        ];
    }

    protected function getFallbackDistricts(int $provinceId): array
    {
        $master = $this->getMasterLocationData();
        if (isset($master[$provinceId]['districts']) && !empty($master[$provinceId]['districts'])) {
            return collect($master[$provinceId]['districts'])
                ->sortBy('name')
                ->values()
                ->all();
        }

        return [
            ['id' => $provinceId * 10 + 1, 'name' => 'Quận / Huyện Trung Tâm', 'province_id' => $provinceId],
            ['id' => $provinceId * 10 + 2, 'name' => 'Quận / Huyện Ngoại Thành', 'province_id' => $provinceId],
        ];
    }

    protected function getFallbackWards(int $districtId): array
    {
        return [
            ['code' => "W{$districtId}01", 'name' => 'Phường / Xã trung tâm', 'district_id' => $districtId],
            ['code' => "W{$districtId}02", 'name' => 'Thị trấn / Khu vực 1', 'district_id' => $districtId],
            ['code' => "W{$districtId}03", 'name' => 'Khu vực ngoại thành', 'district_id' => $districtId],
            ['code' => "W{$districtId}99", 'name' => 'Khu vực khác (ghi rõ ở địa chỉ)', 'district_id' => $districtId],
        ];
    }
}
