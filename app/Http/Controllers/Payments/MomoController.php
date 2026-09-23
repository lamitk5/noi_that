<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\Payments\MomoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MomoController extends Controller
{
    public function __construct(
        protected MomoService $momoService
    ) {}

    public function create(Request $request, string $orderCode): RedirectResponse
    {
        $order = Order::where('order_code', $orderCode)->firstOrFail();

        if ((int) $order->user_id !== (int) $request->user()?->id) {
            abort(403, 'Bạn không có quyền thực hiện thanh toán cho đơn hàng này.');
        }

        if ($order->payment_method !== 'momo') {
            return redirect()
                ->route('orders.track')
                ->with('error', 'Phương thức thanh toán của đơn hàng không phải là MoMo.');
        }

        if ($order->payment_status === 'paid') {
            return redirect()
                ->route('checkout.success', $order->order_code)
                ->with('error', 'Đơn hàng này đã được thanh toán thành công.');
        }

        if ($order->order_status === 'cancelled') {
            return redirect()
                ->route('orders.track')
                ->with('error', 'Đơn hàng đã bị hủy, không thể tiến hành thanh toán.');
        }

        try {
            $reference = 'MOMO_' . $order->id . '_' . time() . '_' . strtoupper(Str::random(4));
            $requestId = (string) Str::uuid();

            $transaction = PaymentTransaction::create([
                'order_id' => $order->id,
                'provider' => 'momo',
                'provider_reference' => $reference,
                'request_id' => $requestId,
                'amount' => $order->total_price,
                'status' => 'pending',
            ]);

            $payUrl = $this->momoService->createPayment($order, $transaction);

            return redirect()->away($payUrl);
        } catch (\Throwable $e) {
            return redirect()
                ->route('checkout.success', $order->order_code)
                ->with('error', $e->getMessage());
        }
    }

    public function return(Request $request): View
    {
        $orderId = $request->query('orderId');
        $transaction = null;
        $order = null;

        if ($orderId) {
            $transaction = PaymentTransaction::where('provider', 'momo')
                ->where('provider_reference', $orderId)
                ->with('order')
                ->first();
            $order = $transaction?->order;
        }

        $isValidSignature = $this->momoService->verifyCallbackSignature($request->query());
        $resultCode = $request->query('resultCode');

        return view('payments.result', [
            'provider' => 'MoMo',
            'order' => $order,
            'transaction' => $transaction,
            'isValidSignature' => $isValidSignature,
            'responseCode' => $resultCode,
        ]);
    }

    public function ipn(Request $request): Response
    {
        $data = $request->all();

        if (! $this->momoService->verifyCallbackSignature($data)) {
            return response()->noContent(400);
        }

        if (($data['partnerCode'] ?? '') !== config('services.momo.partner_code')) {
            return response()->noContent(400);
        }

        $orderId = $data['orderId'] ?? null;
        $transaction = PaymentTransaction::where('provider', 'momo')
            ->where('provider_reference', $orderId)
            ->with('order')
            ->first();

        if (! $transaction || ! $transaction->order || ($transaction->request_id && $transaction->request_id !== ($data['requestId'] ?? ''))) {
            return response()->noContent(404);
        }

        if ((float) ($data['amount'] ?? 0) !== (float) $transaction->amount) {
            return response()->noContent(400);
        }

        if ($transaction->isSuccess() || $transaction->order->payment_status === 'paid') {
            return response()->noContent(204);
        }

        DB::transaction(function () use ($data, $transaction) {
            $resultCode = (int) ($data['resultCode'] ?? -1);
            $transId = $data['transId'] ?? null;

            if ($resultCode === 0) {
                $transaction->update([
                    'status' => 'success',
                    'provider_transaction_id' => $transId,
                    'response_code' => (string) $resultCode,
                    'paid_at' => now(),
                ]);
                $transaction->order->update(['payment_status' => 'paid']);
            } else {
                $transaction->update([
                    'status' => 'failed',
                    'response_code' => (string) $resultCode,
                    'failed_at' => now(),
                ]);
            }
        });

        return response()->noContent(204);
    }
}
