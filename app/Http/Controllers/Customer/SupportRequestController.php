<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SupportRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportRequestController extends Controller
{
    private const MAX_PHOTOS = 4;

    public function index(Request $request): View
    {
        $type = $request->query('type');

        $query = SupportRequest::where('user_id', Auth::id())->with('order')->latest();
        if (array_key_exists((string) $type, SupportRequest::TYPES)) {
            $query->where('type', $type);
        }

        return view('support.index', [
            'requests' => $query->paginate(10)->withQueryString(),
            'currentType' => $type,
            'counts' => [
                'all' => SupportRequest::where('user_id', Auth::id())->count(),
                SupportRequest::TYPE_RETURN => SupportRequest::where('user_id', Auth::id())->where('type', SupportRequest::TYPE_RETURN)->count(),
                SupportRequest::TYPE_COMPLAINT => SupportRequest::where('user_id', Auth::id())->where('type', SupportRequest::TYPE_COMPLAINT)->count(),
            ],
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $type = $request->query('type') === SupportRequest::TYPE_RETURN
            ? SupportRequest::TYPE_RETURN
            : SupportRequest::TYPE_COMPLAINT;

        $order = null;
        if ($code = $request->query('order')) {
            $order = Order::ownedBy(Auth::user())->where('order_code', $code)->with('items.variant.product.images')->first();
        }

        if ($type === SupportRequest::TYPE_RETURN) {
            if (!$order) {
                return redirect()->route('orders.index', ['status' => 'completed'])
                    ->with('error', 'Hãy chọn đơn hàng đã giao thành công để gửi yêu cầu hoàn hàng.');
            }
            if ($error = $this->returnBlocker($order)) {
                return redirect()->route('orders.show', $order->order_code)->with('error', $error);
            }
        }

        return view('support.create', [
            'type' => $type,
            'order' => $order,
            'orders' => Order::ownedBy(Auth::user())->latest()->limit(30)->get(['id', 'order_code', 'created_at', 'total_price', 'order_status']),
            'reasons' => SupportRequest::REASONS[$type],
            'maxPhotos' => self::MAX_PHOTOS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $type = $request->input('type') === SupportRequest::TYPE_RETURN
            ? SupportRequest::TYPE_RETURN
            : SupportRequest::TYPE_COMPLAINT;
        $isReturn = $type === SupportRequest::TYPE_RETURN;

        $validated = $request->validate([
            'order_code' => [$isReturn ? 'required' : 'nullable', 'string', 'max:50'],
            'reason' => ['required', Rule::in(array_keys(SupportRequest::REASONS[$type]))],
            'subject' => ['nullable', 'string', 'max:150'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'items' => [$isReturn ? 'required' : 'nullable', 'array'],
            'items.*' => ['nullable', 'integer', 'min:0', 'max:999'],
            'resolution' => [$isReturn ? 'required' : 'nullable', Rule::in(array_keys(SupportRequest::RESOLUTIONS))],
            'refund_account' => ['nullable', 'string', 'max:255', Rule::requiredIf($isReturn && $request->input('resolution') === 'refund')],
            'photos' => [$isReturn ? 'required' : 'nullable', 'array', 'max:'.self::MAX_PHOTOS],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'order_code.required' => 'Vui lòng chọn đơn hàng cần hoàn.',
            'reason.required' => 'Vui lòng chọn lý do.',
            'description.required' => 'Vui lòng mô tả chi tiết vấn đề.',
            'description.min' => 'Mô tả cần ít nhất 10 ký tự để chúng tôi hiểu rõ vấn đề.',
            'items.required' => 'Vui lòng chọn sản phẩm cần hoàn.',
            'resolution.required' => 'Vui lòng chọn hình thức xử lý.',
            'refund_account.required' => 'Vui lòng nhập tài khoản nhận tiền hoàn (ngân hàng, số tài khoản, chủ tài khoản).',
            'photos.required' => 'Vui lòng đính kèm ít nhất 1 ảnh chụp sản phẩm.',
            'photos.max' => 'Chỉ được đính kèm tối đa '.self::MAX_PHOTOS.' ảnh.',
            'photos.*.image' => 'Tệp đính kèm phải là ảnh.',
            'photos.*.mimes' => 'Ảnh phải có định dạng JPG, PNG hoặc WEBP.',
            'photos.*.max' => 'Mỗi ảnh tối đa 5MB.',
        ]);

        $order = null;
        if (!empty($validated['order_code'])) {
            $order = Order::ownedBy(Auth::user())->where('order_code', $validated['order_code'])->with('items')->first();
            if (!$order) {
                return back()->withInput()->withErrors(['order_code' => 'Không tìm thấy đơn hàng này trong tài khoản của bạn.']);
            }
        }

        $items = null;
        if ($isReturn) {
            if ($error = $this->returnBlocker($order)) {
                return redirect()->route('orders.show', $order->order_code)->with('error', $error);
            }

            $items = [];
            foreach ($order->items as $item) {
                $qty = min((int) ($validated['items'][$item->id] ?? 0), (int) $item->quantity);
                if ($qty > 0) {
                    $items[] = [
                        'order_item_id' => $item->id,
                        'product_variant_id' => $item->product_variant_id,
                        'product_name' => $item->product_name,
                        'variant_info' => $item->variant_info,
                        'quantity' => $qty,
                        'price' => (float) $item->price,
                    ];
                }
            }
            if (!$items) {
                return back()->withInput()->withErrors(['items' => 'Vui lòng chọn ít nhất 1 sản phẩm và số lượng cần hoàn.']);
            }
        }

        $supportRequest = SupportRequest::create([
            'user_id' => Auth::id(),
            'order_id' => $order?->id,
            'type' => $type,
            'reason' => $validated['reason'],
            'subject' => $validated['subject'] ?? null,
            'description' => $validated['description'],
            'items' => $items,
            'photos' => $this->storePhotos($request),
            'resolution' => $isReturn ? $validated['resolution'] : null,
            'refund_account' => $isReturn && $validated['resolution'] === 'refund' ? $validated['refund_account'] : null,
            'status' => SupportRequest::STATUS_PENDING,
        ]);

        return redirect()->route('support.show', $supportRequest)
            ->with('success', 'Đã gửi yêu cầu '.$supportRequest->code.'. Mộc An sẽ phản hồi trong vòng 24 giờ làm việc.');
    }

    public function show(SupportRequest $supportRequest): View
    {
        abort_unless($supportRequest->user_id === Auth::id(), 404);

        $supportRequest->load('order');

        return view('support.show', ['req' => $supportRequest]);
    }

    private function returnBlocker(Order $order): ?string
    {
        if ($order->order_status !== Order::STATUS_COMPLETED) {
            return 'Chỉ có thể yêu cầu hoàn hàng khi đơn đã giao thành công.';
        }
        if ($order->returnDeadline() && now()->gt($order->returnDeadline())) {
            return 'Đơn hàng đã quá thời hạn '.SupportRequest::RETURN_WINDOW_DAYS.' ngày để yêu cầu hoàn hàng. Bạn vẫn có thể gửi khiếu nại.';
        }
        if ($order->hasOpenReturn()) {
            return 'Đơn hàng này đã có một yêu cầu hoàn hàng đang được xử lý.';
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function storePhotos(Request $request): array
    {
        $dir = storage_path('picture');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $names = [];
        foreach (array_slice($request->file('photos', []), 0, self::MAX_PHOTOS) as $photo) {
            $name = 'support-'.date('ymd').'-'.Str::lower(Str::random(20)).'.'.$photo->guessExtension();
            $photo->move($dir, $name);
            $names[] = $name;
        }

        return $names;
    }
}
