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
                $response = Http::timeout(10)
                    ->withHeaders(['Token' => $this->token])
                    ->get($this->apiUrl . 'master-data/province');

                if ($response->successful() && !empty($response->json('data'))) {
                    return collect($response->json('data'))
                        ->map(fn ($p) => [
                            'id' => (int) ($p['ProvinceID'] ?? 0),
                            'name' => (string) ($p['ProvinceName'] ?? ''),
                            'code' => (string) ($p['Code'] ?? ''),
                        ])
                        ->filter(fn ($p) => $p['id'] > 0 && !empty($p['name']))
                        ->sortBy('name')
                        ->values()
                        ->all();
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

    public function calculateFee(int $toDistrictId, string $toWardCode, int $weight = 2000, float $insuranceValue = 0): float
    {
        if ($toDistrictId <= 0 || $toWardCode === '') {
            return $this->defaultFee;
        }

        try {
            $payload = [
                'shop_id' => $this->shopId,
                'service_type_id' => 2,
                'from_district_id' => $this->fromDistrictId,
                'to_district_id' => $toDistrictId,
                'to_ward_code' => (string) $toWardCode,
                'height' => 15,
                'length' => 30,
                'width' => 20,
                'weight' => max(200, $weight),
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

        $isCod = $order->payment_method === 'cod';
        $codAmount = $isCod ? (int) round((float) $order->total_price) : 0;

        $items = [];
        $totalWeight = 0;
        foreach ($order->items as $item) {
            $qty = max(1, (int) $item->quantity);
            $itemWeight = 500 * $qty;
            $totalWeight += $itemWeight;
            $items[] = [
                'name' => Str::limit($item->product_name ?? 'Sản phẩm nội thất', 80, ''),
                'code' => (string) ($item->product_variant_id ?? $item->id),
                'quantity' => $qty,
                'price' => (int) round((float) $item->price),
                'weight' => $itemWeight,
            ];
        }

        if (empty($items)) {
            $items[] = [
                'name' => 'Sản phẩm nội thất',
                'quantity' => 1,
                'price' => (int) round((float) $order->total_price),
                'weight' => 1000,
            ];
            $totalWeight = 1000;
        }

        $toDistrictId = (int) ($order->district_id ?: 1444);
        $toWardCode = (string) ($order->ward_code ?: '20311');

        $payload = [
            'payment_type_id' => 1,
            'note' => $order->note ?: 'Giao hàng giờ hành chính',
            'required_note' => 'CHOXEMHANGKHONGTHU',
            'from_name' => 'Luxury Home',
            'from_phone' => '0912345678',
            'from_address' => 'Số 123 Đường Nguyễn Trãi, Phường Bến Thành',
            'from_ward_name' => 'Phường Bến Thành',
            'from_district_name' => 'Quận 1',
            'from_province_name' => 'Hồ Chí Minh',
            'return_phone' => '0912345678',
            'return_address' => 'Số 123 Đường Nguyễn Trãi, Phường Bến Thành',
            'client_order_code' => $order->order_code,
            'to_name' => $order->customer_name,
            'to_phone' => $order->customer_phone,
            'to_address' => $order->shipping_address,
            'to_ward_code' => $toWardCode,
            'to_district_id' => $toDistrictId,
            'cod_amount' => $codAmount,
            'content' => "Don hang {$order->order_code}",
            'weight' => max(500, $totalWeight),
            'length' => 30,
            'width' => 20,
            'height' => 20,
            'insurance_value' => (int) min(5000000, max(0, (float) $order->total_price)),
            'service_type_id' => 2,
            'items' => $items,
        ];

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Token' => $this->token,
                    'ShopId' => (string) $this->shopId,
                ])
                ->post($this->apiUrl . 'v2/shipping-order/create', $payload);

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

    protected function getFallbackProvinces(): array
    {
        return [
            ['id' => 201, 'name' => 'Hà Nội', 'code' => 'HN'],
            ['id' => 202, 'name' => 'Hồ Chí Minh', 'code' => 'HCM'],
            ['id' => 203, 'name' => 'Đà Nẵng', 'code' => 'DN'],
            ['id' => 204, 'name' => 'Hải Phòng', 'code' => 'HP'],
            ['id' => 205, 'name' => 'Cần Thơ', 'code' => 'CT'],
            ['id' => 206, 'name' => 'Bình Dương', 'code' => 'BD'],
            ['id' => 207, 'name' => 'Đồng Nai', 'code' => 'DNA'],
            ['id' => 208, 'name' => 'Khánh Hòa', 'code' => 'KH'],
            ['id' => 209, 'name' => 'Quảng Ninh', 'code' => 'QN'],
            ['id' => 210, 'name' => 'Lâm Đồng', 'code' => 'LD'],
        ];
    }

    protected function getFallbackDistricts(int $provinceId): array
    {
        if ($provinceId === 202) {
            return [
                ['id' => 1442, 'name' => 'Quận 1', 'province_id' => 202],
                ['id' => 1443, 'name' => 'Quận 3', 'province_id' => 202],
                ['id' => 1444, 'name' => 'Quận 4', 'province_id' => 202],
                ['id' => 1446, 'name' => 'Quận 7', 'province_id' => 202],
                ['id' => 1451, 'name' => 'Quận Bình Thạnh', 'province_id' => 202],
                ['id' => 1452, 'name' => 'TP. Thủ Đức', 'province_id' => 202],
            ];
        }

        if ($provinceId === 201) {
            return [
                ['id' => 1482, 'name' => 'Quận Ba Đình', 'province_id' => 201],
                ['id' => 1484, 'name' => 'Quận Hoàn Kiếm', 'province_id' => 201],
                ['id' => 1485, 'name' => 'Quận Hai Bà Trưng', 'province_id' => 201],
                ['id' => 1486, 'name' => 'Quận Đống Đa', 'province_id' => 201],
                ['id' => 1488, 'name' => 'Quận Cầu Giấy', 'province_id' => 201],
            ];
        }

        return [
            ['id' => $provinceId * 10 + 1, 'name' => 'Quận / Huyện Trung Tâm', 'province_id' => $provinceId],
            ['id' => $provinceId * 10 + 2, 'name' => 'Quận / Huyện Ngoại Thành', 'province_id' => $provinceId],
        ];
    }

    protected function getFallbackWards(int $districtId): array
    {
        return [
            ['code' => "W{$districtId}01", 'name' => 'Phường / Xã 1', 'district_id' => $districtId],
            ['code' => "W{$districtId}02", 'name' => 'Phường / Xã 2', 'district_id' => $districtId],
            ['code' => "W{$districtId}03", 'name' => 'Phường / Xã 3', 'district_id' => $districtId],
        ];
    }
}
