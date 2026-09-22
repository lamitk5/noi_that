<?php

namespace App\Services\Ai;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Voucher;
use App\Models\Wishlist;
use App\Services\CartService;
use App\Services\RecommendationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AiToolRegistry
{
    protected CartService $cartService;
    protected RecommendationService $recommendationService;

    public function __construct(CartService $cartService, RecommendationService $recommendationService)
    {
        $this->cartService = $cartService;
        $this->recommendationService = $recommendationService;
    }

    /**
     * Get tool specifications for LLM function calling.
     */
    public function getToolDeclarations(): array
    {
        return [
            [
                'name' => 'search_products',
                'description' => 'Tìm kiếm sản phẩm nội thất theo từ khóa, tầm giá (ngân sách), danh mục, màu sắc, chất liệu hoặc tình trạng tồn kho.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'query' => ['type' => 'STRING', 'description' => 'Từ khóa tìm kiếm (ví dụ: sofa, bàn ăn, giường)'],
                        'category_slug' => ['type' => 'STRING', 'description' => 'Slug danh mục: phong-khach, phong-an, phong-ngu, phong-lam-viec'],
                        'max_price' => ['type' => 'NUMBER', 'description' => 'Giá tối đa bằng VNĐ (ví dụ: 15000000)'],
                        'min_price' => ['type' => 'NUMBER', 'description' => 'Giá tối thiểu bằng VNĐ'],
                        'color' => ['type' => 'STRING', 'description' => 'Màu sắc mong muốn (ví dụ: nâu, kem, xám, đen, gỗ tự nhiên)'],
                        'material' => ['type' => 'STRING', 'description' => 'Chất liệu (ví dụ: sồi, óc chó, nỉ, da)'],
                        'in_stock_only' => ['type' => 'BOOLEAN', 'description' => 'Chỉ lấy sản phẩm còn hàng'],
                        'sort' => ['type' => 'STRING', 'description' => 'Sắp xếp: price_asc, price_desc, latest'],
                    ],
                ],
            ],
            [
                'name' => 'get_product_detail',
                'description' => 'Xem thông tin chi tiết một sản phẩm, bao gồm tất cả phiên bản màu sắc, kích thước, giá và tồn kho thực tế.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'slug' => ['type' => 'STRING', 'description' => 'Slug hoặc mã SKU của sản phẩm'],
                        'id' => ['type' => 'INTEGER', 'description' => 'ID của sản phẩm nếu có'],
                    ],
                ],
            ],
            [
                'name' => 'check_inventory',
                'description' => 'Kiểm tra tồn kho chính xác của một sản phẩm hoặc biến thể cụ thể (màu sắc/kích thước/chất liệu).',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'slug_or_id' => ['type' => 'STRING', 'description' => 'Slug hoặc ID của sản phẩm'],
                        'color' => ['type' => 'STRING', 'description' => 'Tùy chọn màu sắc cần kiểm tra'],
                        'size' => ['type' => 'STRING', 'description' => 'Tùy chọn kích thước cần kiểm tra'],
                    ],
                    'required' => ['slug_or_id'],
                ],
            ],
            [
                'name' => 'compare_products',
                'description' => 'So sánh chi tiết 2 đến 4 sản phẩm nội thất về giá, kích thước, chất liệu, xuất xứ và tồn kho.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'product_ids_or_slugs' => [
                            'type' => 'ARRAY',
                            'items' => ['type' => 'STRING'],
                            'description' => 'Danh sách từ 2 đến 4 slug hoặc ID sản phẩm cần so sánh',
                        ],
                    ],
                    'required' => ['product_ids_or_slugs'],
                ],
            ],
            [
                'name' => 'recommend_products',
                'description' => 'Gợi ý sản phẩm phù hợp dựa theo lịch sử xem, bán chạy hoặc sản phẩm mua cùng.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'category_slug' => ['type' => 'STRING', 'description' => 'Slug danh mục cần gợi ý'],
                        'current_product_id' => ['type' => 'INTEGER', 'description' => 'ID sản phẩm khách đang xem nếu có'],
                    ],
                ],
            ],
            [
                'name' => 'get_active_vouchers',
                'description' => 'Lấy danh sách mã giảm giá và khuyến mãi đang có hiệu lực tại Mộc An.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'min_order_value' => ['type' => 'NUMBER', 'description' => 'Giá trị đơn hàng dự kiến của khách (VNĐ)'],
                    ],
                ],
            ],
            [
                'name' => 'get_order_status',
                'description' => 'Tra cứu tình trạng đơn hàng của khách hàng đã đăng nhập. Yêu cầu mã đơn hàng.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'order_code' => ['type' => 'STRING', 'description' => 'Mã đơn hàng (ví dụ: MA-2026-...)'],
                    ],
                    'required' => ['order_code'],
                ],
            ],
            [
                'name' => 'get_recent_orders',
                'description' => 'Lấy danh sách các đơn hàng gần đây của khách hàng đang đăng nhập.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'limit' => ['type' => 'INTEGER', 'description' => 'Số đơn hàng cần lấy (mặc định: 3)'],
                    ],
                ],
            ],
            [
                'name' => 'add_to_cart',
                'description' => 'Thêm một sản phẩm (hoặc biến thể cụ thể) vào giỏ hàng của khách.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'variant_id' => ['type' => 'INTEGER', 'description' => 'ID biến thể sản phẩm'],
                        'quantity' => ['type' => 'INTEGER', 'description' => 'Số lượng cần thêm (mặc định: 1)'],
                    ],
                    'required' => ['variant_id'],
                ],
            ],
            [
                'name' => 'create_support_ticket',
                'description' => 'Tạo phiếu yêu cầu hỗ trợ khách hàng khi vấn đề cần đội ngũ tư vấn viên giải quyết.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'subject' => ['type' => 'STRING', 'description' => 'Tiêu đề yêu cầu hỗ trợ'],
                        'message' => ['type' => 'STRING', 'description' => 'Mô tả chi tiết nội dung cần trợ giúp'],
                        'category' => ['type' => 'STRING', 'description' => 'Phân loại: don_hang, san_pham, bao_hanh, tu_van'],
                    ],
                    'required' => ['subject', 'message'],
                ],
            ],
        ];
    }

    /**
     * Execute a tool by name with arguments and context.
     *
     * @param string $toolName
     * @param array $args
     * @param array $context ['user' => ?User, 'session_id' => ?string]
     * @return array ['text' => string, 'card_type' => ?string, 'card_data' => mixed]
     */
    public function execute(string $toolName, array $args, array $context): array
    {
        $user = $context['user'] ?? Auth::user();

        return match ($toolName) {
            'search_products' => $this->searchProducts($args),
            'get_product_detail' => $this->getProductDetail($args),
            'check_inventory' => $this->checkInventory($args),
            'compare_products' => $this->compareProducts($args),
            'recommend_products' => $this->recommendProducts($args, $user),
            'get_active_vouchers' => $this->getActiveVouchers($args),
            'get_order_status' => $this->getOrderStatus($args, $user),
            'get_recent_orders' => $this->getRecentOrders($args, $user),
            'add_to_cart' => $this->addToCart($args, $user),
            'create_support_ticket' => $this->createSupportTicket($args, $user),
            default => [
                'text' => "Công cụ '{$toolName}' không được hỗ trợ.",
                'card_type' => null,
                'card_data' => null,
            ],
        };
    }

    /**
     * Helper to execute tool and return normalized envelope.
     */
    public function executeTool(string $toolName, array $args, array $context = []): array
    {
        $res = $this->execute($toolName, $args, $context);
        $text = $res['text'] ?? '';

        $isAuthRequired = str_contains($text, 'đăng nhập');
        $isForbidden = str_contains($text, 'Không tìm thấy đơn hàng') || str_contains($text, 'không thuộc tài khoản');
        $isUnsupported = str_contains($text, 'không được hỗ trợ');

        $success = !$isAuthRequired && !$isForbidden && !$isUnsupported && !empty($res['card_type']);
        if (!$success && empty($res['card_type']) && !empty($res['text']) && !$isAuthRequired && !$isForbidden && !$isUnsupported) {
            $success = true;
        }

        return [
            'success' => $success,
            'text' => $text,
            'card_type' => $res['card_type'] ?? null,
            'card' => $res['card_type'] ? ['type' => $res['card_type'], 'data' => $res['card_data']] : null,
            'data' => is_array($res['card_data']) ? $res['card_data'] : ['result' => $res['card_data']],
            'error_code' => $isAuthRequired ? 'AUTH_REQUIRED' : ($isForbidden ? 'FORBIDDEN_ORDER' : null),
        ];
    }


    protected function searchProducts(array $args): array
    {
        $query = Product::query()
            ->with(['category', 'primaryImage', 'variants'])
            ->where('is_active', true);

        if (!empty($args['query'])) {
            $keyword = trim($args['query']);
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('sku', 'like', "%{$keyword}%")
                    ->orWhere('short_description', 'like', "%{$keyword}%");
            });
        }

        if (!empty($args['category_slug'])) {
            $query->whereHas('category', function ($q) use ($args) {
                $q->where('slug', $args['category_slug']);
            });
        }

        if (!empty($args['max_price'])) {
            $query->where('base_price', '<=', (float) $args['max_price']);
        }

        if (!empty($args['min_price'])) {
            $query->where('base_price', '>=', (float) $args['min_price']);
        }

        if (!empty($args['color']) || !empty($args['material'])) {
            $query->whereHas('variants', function ($q) use ($args) {
                if (!empty($args['color'])) {
                    $q->where('color', 'like', "%{$args['color']}%");
                }
                if (!empty($args['material'])) {
                    $q->where('material', 'like', "%{$args['material']}%");
                }
            });
        }

        if (!empty($args['in_stock_only'])) {
            $query->whereHas('variants', function ($q) {
                $q->where('stock', '>', 0);
            });
        }

        $sort = $args['sort'] ?? 'latest';
        if ($sort === 'price_asc') {
            $query->orderBy('base_price', 'asc');
        } elseif ($sort === 'price_desc') {
            $query->orderBy('base_price', 'desc');
        } else {
            $query->latest('id');
        }

        $products = $query->take(6)->get();

        if ($products->isEmpty()) {
            return [
                'text' => 'Không tìm thấy sản phẩm nào khớp với tiêu chí tìm kiếm.',
                'card_type' => null,
                'card_data' => [],
            ];
        }

        $cards = $products->map(function ($p) {
            $firstInStockVariant = $p->variants->firstWhere('stock', '>', 0) ?? $p->variants->first();
            return [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'sku' => $p->sku,
                'base_price' => (float) $p->base_price,
                'formatted_price' => number_format((float) $p->base_price, 0, ',', '.') . '₫',
                'category_name' => $p->category?->name ?? 'Mộc An',
                'image_url' => $p->primaryImage?->image_path ?? 'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=400&q=80',
                'total_stock' => $p->totalStock(),
                'is_in_stock' => !$p->isOutOfStock(),
                'default_variant_id' => $firstInStockVariant?->id,
                'url' => route('products.show', $p->slug),
            ];
        })->values()->all();

        $textSummary = "Tìm thấy " . count($cards) . " sản phẩm phù hợp:\n";
        foreach ($cards as $c) {
            $stockText = $c['is_in_stock'] ? "Còn hàng ({$c['total_stock']} sp)" : "Hết hàng";
            $textSummary .= "- {$c['name']} (SKU: {$c['sku']}) - Giá: {$c['formatted_price']} - {$stockText}\n";
        }

        return [
            'text' => $textSummary,
            'card_type' => 'product_list',
            'card_data' => $cards,
        ];
    }

    protected function getProductDetail(array $args): array
    {
        $query = Product::query()->with(['category', 'images', 'variants'])->where('is_active', true);

        $productId = $args['product_id'] ?? $args['id'] ?? null;
        if (!empty($productId)) {
            $query->where('id', $productId);
        } elseif (!empty($args['slug'])) {
            $query->where(function ($q) use ($args) {
                $q->where('slug', $args['slug'])->orWhere('sku', $args['slug']);
            });
        } else {
            return ['text' => 'Vui lòng cung cấp mã SKU hoặc slug sản phẩm.', 'card_type' => null, 'card_data' => null];
        }

        $product = $query->first();

        if (!$product) {
            return ['text' => 'Không tìm thấy thông tin sản phẩm này trong hệ thống Mộc An.', 'card_type' => null, 'card_data' => null];
        }

        $variants = $product->variants;
        $totalStock = $product->totalStock();
        $inStock = !$product->isOutOfStock();

        $materials = $variants->pluck('material')->filter()->unique()->implode(', ') ?: ($product->material ?? null);
        $dimensions = $variants->pluck('size')->filter()->unique()->implode(', ') ?: ($product->dimensions ?? null);

        $cardData = [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'base_price' => (float) $product->base_price,
            'formatted_price' => number_format((float) $product->base_price, 0, ',', '.') . '₫',
            'material' => $materials,
            'dimensions' => $dimensions,
            'warranty_months' => $product->warranty_months ?? 12,
            'category_name' => $product->category?->name ?? 'Nội thất',
            'total_stock' => $totalStock,
            'is_in_stock' => $inStock,
            'description' => strip_tags($product->description ?? $product->short_description ?? ''),
            'url' => route('products.show', $product->slug),
            'variants' => $variants->map(fn ($v) => [
                'id' => $v->id,
                'sku' => $v->sku,
                'color' => $v->color,
                'size' => $v->size,
                'stock' => (int) $v->stock,
                'price' => (float) $v->price,
                'formatted_price' => number_format((float) $v->price, 0, ',', '.') . '₫',
            ])->values()->all(),
        ];

        $stockStatusText = $inStock ? "Còn hàng ({$totalStock} sản phẩm)" : "Tạm hết hàng";
        $text = "Thông tin chi tiết **{$product->name}**:\n"
            . "- Giá: **{$cardData['formatted_price']}**\n"
            . "- Trạng thái: **{$stockStatusText}**\n";

        if ($product->material) {
            $text .= "- Chất liệu: {$product->material}\n";
        }
        if ($product->dimensions) {
            $text .= "- Kích thước: {$product->dimensions}\n";
        }
        if ($product->warranty_months) {
            $text .= "- Bảo hành: {$product->warranty_months} tháng\n";
        }

        return [
            'text' => $text,
            'card_type' => 'product_detail',
            'card_data' => $cardData,
        ];
    }

    protected function checkInventory(array $args): array
    {
        $idOrSlug = $args['product_id'] ?? $args['id'] ?? $args['slug_or_id'] ?? $args['slug'] ?? '';
        $product = Product::query()
            ->with('variants')
            ->where('is_active', true)
            ->where(function ($q) use ($idOrSlug) {
                if (is_numeric($idOrSlug)) {
                    $q->where('id', $idOrSlug);
                }
                $q->orWhere('slug', $idOrSlug)->orWhere('sku', $idOrSlug);
            })
            ->first();

        if (!$product) {
            return ['text' => "Không tìm thấy sản phẩm '{$idOrSlug}'.", 'card_type' => null, 'card_data' => null];
        }

        $variants = $product->variants;

        if (!empty($args['color'])) {
            $variants = $variants->filter(fn ($v) => mb_stripos($v->color ?? '', $args['color']) !== false);
        }
        if (!empty($args['size'])) {
            $variants = $variants->filter(fn ($v) => mb_stripos($v->size ?? '', $args['size']) !== false);
        }

        if ($variants->isEmpty()) {
            return [
                'text' => "Sản phẩm '{$product->name}' hiện không có phiên bản khớp với màu/kích thước bạn yêu cầu.",
                'card_type' => null,
                'card_data' => null,
            ];
        }

        $resText = "Tình trạng tồn kho của '{$product->name}':\n";
        $stockData = [];

        foreach ($variants as $v) {
            $desc = implode(' - ', array_filter([$v->color, $v->size, $v->material])) ?: 'Mặc định';
            $status = $v->stock > 0 ? "Còn {$v->stock} sản phẩm" : "Hết hàng";
            $resText .= "- {$desc}: {$status} (Giá: " . number_format((float) $v->price, 0, ',', '.') . "₫)\n";
            $stockData[] = [
                'variant_id' => $v->id,
                'desc' => $desc,
                'stock' => (int) $v->stock,
                'price' => (float) $v->price,
            ];
        }

        return [
            'text' => $resText,
            'card_type' => 'inventory_status',
            'card_data' => [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'stock_status' => $product->isOutOfStock() ? 'out_of_stock' : ($product->isLowStock() ? 'low_stock' : 'in_stock'),
                'total_stock' => $product->totalStock(),
                'items' => $stockData,
            ],
        ];
    }

    protected function compareProducts(array $args): array
    {
        $identifiers = (array) ($args['product_ids'] ?? $args['product_ids_or_slugs'] ?? []);
        if (count($identifiers) < 2) {
            return ['text' => 'Vui lòng cung cấp ít nhất 2 sản phẩm để so sánh.', 'card_type' => null, 'card_data' => null];
        }

        $identifiers = array_slice($identifiers, 0, 4);

        $products = Product::query()
            ->with(['category', 'primaryImage', 'variants'])
            ->where('is_active', true)
            ->where(function ($q) use ($identifiers) {
                $q->whereIn('id', $identifiers)
                    ->orWhereIn('slug', $identifiers)
                    ->orWhereIn('sku', $identifiers);
            })
            ->get();

        if ($products->count() < 2) {
            return ['text' => 'Không tìm đủ các sản phẩm tương ứng trong hệ thống để so sánh.', 'card_type' => null, 'card_data' => null];
        }

        $compareList = $products->map(function ($p) {
            $materials = $p->variants->pluck('material')->filter()->unique()->values();
            $sizes = $p->variants->pluck('size')->filter()->unique()->values();
            $colors = $p->variants->pluck('color')->filter()->unique()->values();

            return [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'formatted_price' => number_format((float) $p->base_price, 0, ',', '.') . '₫',
                'base_price' => (float) $p->base_price,
                'category' => $p->category?->name ?? 'Mộc An',
                'materials' => $materials->isNotEmpty() ? $materials->join(', ') : 'Gỗ tự nhiên cao cấp',
                'sizes' => $sizes->isNotEmpty() ? $sizes->join(', ') : 'Tiêu chuẩn',
                'colors' => $colors->isNotEmpty() ? $colors->join(', ') : 'Tự nhiên',
                'stock' => $p->totalStock(),
                'image_url' => $p->primaryImage?->image_path ?? 'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=400&q=80',
                'url' => route('products.show', $p->slug),
            ];
        })->values()->all();

        $text = "Bảng so sánh " . count($compareList) . " sản phẩm:\n";
        foreach ($compareList as $item) {
            $text .= "- {$item['name']}: Giá {$item['formatted_price']} | Chất liệu: {$item['materials']} | Kho: {$item['stock']} sp\n";
        }

        return [
            'text' => $text,
            'card_type' => 'product_comparison',
            'card_data' => $compareList,
        ];
    }

    protected function recommendProducts(array $args, ?User $user): array
    {
        $query = Product::query()->with(['category', 'primaryImage', 'variants'])->where('is_active', true);

        if (!empty($args['category_slug'])) {
            $query->whereHas('category', function ($q) use ($args) {
                $q->where('slug', $args['category_slug']);
            });
        }

        $items = $query->inRandomOrder()->take(4)->get();

        $cards = $items->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'formatted_price' => number_format((float) $p->base_price, 0, ',', '.') . '₫',
                'category_name' => $p->category?->name,
                'image_url' => $p->primaryImage?->image_path ?? 'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=400&q=80',
                'url' => route('products.show', $p->slug),
            ];
        })->values()->all();

        $text = "Gợi ý các món đồ phù hợp cho không gian sống của bạn:\n";
        foreach ($cards as $c) {
            $text .= "- {$c['name']} - Giá: {$c['formatted_price']}\n";
        }

        return [
            'text' => $text,
            'card_type' => 'product_list',
            'card_data' => $cards,
        ];
    }

    protected function getActiveVouchers(array $args): array
    {
        $now = now();
        $query = Voucher::query()
            ->where('is_active', true)
            ->where('starts_at', '<=', $now)
            ->where('expires_at', '>=', $now)
            ->where(function ($q) {
                $q->whereNull('usage_limit')
                    ->orWhereColumn('used_count', '<', 'usage_limit');
            });

        if (!empty($args['min_order_value'])) {
            $query->where('min_order_amount', '<=', (float) $args['min_order_value']);
        }

        $vouchers = $query->get();

        if ($vouchers->isEmpty()) {
            return [
                'text' => 'Hiện tại chưa có mã giảm giá áp dụng trực tiếp cho giá trị đơn hàng này.',
                'card_type' => null,
                'card_data' => [],
            ];
        }

        $cards = $vouchers->map(function ($v) {
            $discountText = $v->type === 'percent'
                ? "Giảm {$v->value}%" . ($v->max_discount ? " (tối đa " . number_format($v->max_discount, 0, ',', '.') . "₫)" : "")
                : "Giảm " . number_format($v->value, 0, ',', '.') . "₫";

            return [
                'code' => $v->code,
                'discount_text' => $discountText,
                'min_order' => number_format((float) $v->min_order_amount, 0, ',', '.') . '₫',
                'expires_at' => $v->expires_at->format('d/m/Y'),
            ];
        })->values()->all();

        $text = "Các mã ưu đãi hiện có tại Mộc An:\n";
        foreach ($cards as $vc) {
            $text .= "- Mã **{$vc['code']}**: {$vc['discount_text']} (Đơn từ {$vc['min_order']}, HSD: {$vc['expires_at']})\n";
        }

        return [
            'text' => $text,
            'card_type' => 'voucher_list',
            'card_data' => $cards,
        ];
    }

    protected function getOrderStatus(array $args, ?User $user): array
    {
        if (!$user) {
            return [
                'text' => 'Để bảo mật thông tin đơn hàng, quý khách vui lòng đăng nhập tài khoản mua hàng.',
                'card_type' => null,
                'card_data' => null,
            ];
        }

        $orderCode = trim($args['order_code'] ?? '');
        if (empty($orderCode)) {
            return ['text' => 'Vui lòng cung cấp mã đơn hàng cần kiểm tra.', 'card_type' => null, 'card_data' => null];
        }

        // Strict ownership check
        $order = Order::query()
            ->with(['items.product', 'items.variant'])
            ->where('user_id', $user->id)
            ->where('order_code', $orderCode)
            ->first();

        if (!$order) {
            return [
                'text' => "Không tìm thấy đơn hàng mã '{$orderCode}' thuộc tài khoản của bạn.",
                'card_type' => null,
                'card_data' => null,
            ];
        }

        $statusLabels = [
            Order::STATUS_PENDING => 'Chờ xử lý',
            Order::STATUS_CONFIRMED => 'Đã xác nhận',
            Order::STATUS_PACKED => 'Đang đóng gói',
            Order::STATUS_SHIPPING => 'Đang giao hàng',
            Order::STATUS_COMPLETED => 'Hoàn thành',
            Order::STATUS_CANCELED => 'Đã hủy',
        ];

        $paymentStatusLabels = [
            Order::PAYMENT_PENDING => 'Chờ thanh toán',
            Order::PAYMENT_PAID => 'Đã thanh toán',
            Order::PAYMENT_FAILED => 'Thanh toán thất bại',
        ];

        $cardData = [
            'order_code' => $order->order_code,
            'status' => $order->order_status,
            'order_status' => $order->order_status,
            'status_label' => $statusLabels[$order->order_status] ?? $order->order_status,
            'payment_status' => $order->payment_status,
            'payment_status_label' => $paymentStatusLabels[$order->payment_status] ?? $order->payment_status,
            'total_amount' => (float) $order->total_price,
            'formatted_total' => number_format((float) $order->total_price, 0, ',', '.') . '₫',
            'created_at' => $order->created_at->format('d/m/Y H:i'),
            'item_count' => $order->items ? $order->items->count() : 0,
            'items_count' => $order->items ? $order->items->count() : 0,
            'tracking_number' => $order->tracking_code,
            'url' => url('/dat-hang-thanh-cong/' . $order->order_code),
        ];

        $text = "Thông tin đơn hàng **{$order->order_code}**:\n"
            . "- Trạng thái vận chuyển: **{$cardData['status_label']}**\n"
            . "- Trạng thái thanh toán: **{$cardData['payment_status_label']}**\n"
            . "- Tổng tiền: **{$cardData['formatted_total']}**\n"
            . "- Ngày đặt: {$cardData['created_at']}\n";

        if ($order->tracking_code) {
            $text .= "- Mã vận đơn: {$order->tracking_code}\n";
        }

        return [
            'text' => $text,
            'card_type' => 'order_status',
            'card_data' => $cardData,
        ];
    }

    protected function getRecentOrders(array $args, ?User $user): array
    {
        if (!$user) {
            return [
                'text' => 'Quý khách vui lòng đăng nhập tài khoản để xem danh sách đơn hàng đã mua.',
                'card_type' => null,
                'card_data' => null,
            ];
        }

        $limit = min(5, max(1, (int) ($args['limit'] ?? 3)));
        $orders = Order::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->take($limit)
            ->get();

        if ($orders->isEmpty()) {
            return [
                'text' => 'Tài khoản của bạn hiện chưa có đơn hàng nào.',
                'card_type' => null,
                'card_data' => [],
            ];
        }

        $statusLabels = [
            Order::STATUS_PENDING => 'Chờ xử lý',
            Order::STATUS_CONFIRMED => 'Đã xác nhận',
            Order::STATUS_PACKED => 'Đang đóng gói',
            Order::STATUS_SHIPPING => 'Đang giao hàng',
            Order::STATUS_COMPLETED => 'Hoàn thành',
            Order::STATUS_CANCELED => 'Đã hủy',
        ];

        $cards = $orders->map(function ($o) use ($statusLabels) {
            return [
                'order_code' => $o->order_code,
                'status_label' => $statusLabels[$o->order_status] ?? $o->order_status,
                'formatted_total' => number_format((float) $o->total_price, 0, ',', '.') . '₫',
                'date' => $o->created_at->format('d/m/Y'),
                'url' => url('/dat-hang-thanh-cong/' . $o->order_code),
            ];
        })->values()->all();

        $text = "Các đơn hàng gần nhất của bạn:\n";
        foreach ($cards as $c) {
            $text .= "- Đơn **{$c['order_code']}** ({$c['date']}): {$c['formatted_total']} - {$c['status_label']}\n";
        }

        return [
            'text' => $text,
            'card_type' => 'order_list',
            'card_data' => $cards,
        ];
    }

    protected function addToCart(array $args, ?User $user): array
    {
        $variantId = (int) ($args['variant_id'] ?? 0);
        $quantity = max(1, (int) ($args['quantity'] ?? 1));

        $variant = ProductVariant::query()->with('product')->find($variantId);

        if (!$variant || !$variant->product || !$variant->product->is_active) {
            return [
                'text' => 'Không tìm thấy phiên bản sản phẩm phù hợp để thêm vào giỏ.',
                'card_type' => null,
                'card_data' => null,
            ];
        }

        if ($variant->stock <= 0) {
            return [
                'text' => "Phiên bản '{$variant->product->name}' này hiện đã hết hàng trong kho.",
                'card_type' => null,
                'card_data' => null,
            ];
        }

        // Add to cart via existing CartService
        $this->cartService->add($variant->id, $quantity);

        $productName = $variant->product->name;
        $desc = implode(' - ', array_filter([$variant->color, $variant->size, $variant->material]));
        $variantLabel = $desc ? " ({$desc})" : "";

        return [
            'text' => "Đã thêm thành công {$quantity} sản phẩm '{$productName}{$variantLabel}' vào giỏ hàng!",
            'card_type' => 'cart_action_success',
            'card_data' => [
                'variant_id' => $variant->id,
                'product_name' => $productName,
                'quantity' => $quantity,
                'cart_url' => route('cart.index'),
            ],
        ];
    }

    protected function createSupportTicket(array $args, ?User $user): array
    {
        $subject = trim($args['subject'] ?? 'Hỗ trợ khách hàng Mộc An');
        $message = trim($args['message'] ?? '');
        $category = in_array($args['category'] ?? '', ['don_hang', 'san_pham', 'bao_hanh', 'tu_van'])
            ? $args['category']
            : 'tu_van';

        if (empty($message)) {
            return ['text' => 'Vui lòng cung cấp nội dung bạn cần hỗ trợ.', 'card_type' => null, 'card_data' => null];
        }

        $ticketCode = 'TK-' . strtoupper(Str::random(8));

        $ticket = SupportTicket::create([
            'ticket_code' => $ticketCode,
            'user_id' => $user?->id,
            'customer_name' => $user?->name ?? 'Khách hàng',
            'customer_email' => $user?->email ?? 'khach@mocan.vn',
            'category' => $category,
            'subject' => $subject,
            'message' => $message,
            'status' => 'open',
            'priority' => 'normal',
        ]);

        return [
            'text' => "Em đã tạo phiếu hỗ trợ mã **{$ticket->ticket_code}** thành công. Chuyên viên tư vấn Mộc An sẽ liên hệ và giải đáp sớm nhất cho quý khách!",
            'card_type' => 'ticket_created',
            'card_data' => [
                'ticket_code' => $ticket->ticket_code,
                'subject' => $ticket->subject,
                'category' => $ticket->category,
                'status' => 'open',
                'url' => $user ? route('account.tickets.show', $ticket->ticket_code) : null,
            ],
        ];
    }
}
