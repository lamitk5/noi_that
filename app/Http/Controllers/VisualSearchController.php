<?php

namespace App\Http\Controllers;

use App\Services\Ai\GeminiService;
use App\Services\Ai\ProductRecommender;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VisualSearchController extends Controller
{
    public function __construct(
        protected GeminiService $gemini,
        protected ProductRecommender $recommender,
    ) {
    }

    public function index(): View
    {
        return view('products.visual', [
            'configured' => $this->gemini->isConfigured(),
            'analysis' => null,
            'products' => collect(),
        ]);
    }

    public function search(Request $request): View
    {
        if (! $this->gemini->isConfigured()) {
            return view('products.visual', [
                'configured' => false,
                'analysis' => null,
                'products' => collect(),
            ])->withErrors(['image' => 'Chưa cấu hình GEMINI_API_KEY nên chưa phân tích được ảnh.']);
        }

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
        ], [
            'image.required' => 'Hãy chọn một ảnh nội thất.',
            'image.image' => 'File phải là ảnh jpg, png hoặc webp.',
            'image.max' => 'Ảnh tối đa 4 MB.',
        ]);

        $file = $request->file('image');
        $analysis = $this->gemini->generateJsonFromImage(
            'Bạn phân tích ảnh nội thất. Chỉ trả JSON đúng dạng {"style":"","color":"","room":"","keywords":["tu khoa khong dau"]}. keywords tối đa 8 từ tiếng Việt không dấu, là loại đồ, màu hoặc chất liệu nhìn thấy. Không bịa thương hiệu.',
            $file->getMimeType() ?: 'image/jpeg',
            base64_encode((string) file_get_contents($file->getRealPath()))
        );

        if (! is_array($analysis)) {
            return view('products.visual', [
                'configured' => true,
                'analysis' => null,
                'products' => collect(),
            ])->withErrors(['image' => 'Không đọc được nội dung ảnh. Hãy thử một ảnh rõ món nội thất hơn.']);
        }

        $keywords = collect($analysis['keywords'] ?? [])
            ->map(fn ($word) => Str::lower(Str::ascii((string) $word)))
            ->filter()
            ->take(8);

        $text = trim(implode(' ', [
            ...$keywords,
            Str::ascii((string) ($analysis['style'] ?? '')),
            Str::ascii((string) ($analysis['color'] ?? '')),
            Str::ascii((string) ($analysis['room'] ?? '')),
        ]));

        $products = $text === ''
            ? collect()
            : $this->recommender->candidates($this->recommender->extractFilters($text))->take(8);

        return view('products.visual', [
            'configured' => true,
            'analysis' => $analysis,
            'products' => $products,
        ]);
    }
}
