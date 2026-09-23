<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\Payments\VnpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VnpayController extends Controller
{
    public function __construct(
        protected VnpayService $vnpayService
    ) {}

    public function create(Request $request, string $orderCode): RedirectResponse
    {
        $order = Order::where('order_code', $orderCode)->firstOrFail();

        if ((int) $order->user_id !== (int) $request->user()?->id) {
            abort(403, 'Bạn không có quyền thực hiện thanh toán cho đơn hàng này.');
        }

        if ($order->payment_method !== 'vnpay') {
            return redirect()
                ->route('orders.track')
                ->with('error', 'Phương thức thanh toán của đơn hàng không phải là VNPAY.');
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
            $reference = 'VNP_' . $order->id . '_' . time() . '_' . strtoupper(Str::random(4));

            $transaction = PaymentTransaction::create([
                'order_id' => $order->id,
                'provider' => 'vnpay',
                'provider_reference' => $reference,
                'amount' => $order->total_price,
                'status' => 'pending',
            ]);

            $paymentUrl = $this->vnpayService->buildPaymentUrl($order, $transaction, $request->ip());

            return redirect()->away($paymentUrl);
        } catch (\Throwable $e) {
            return redirect()
                ->route('checkout.success', $order->order_code)
                ->with('error', $e->getMessage());
        }
    }

    public function return(Request $request): View
    {
        $vnpTxnRef = $request->query('vnp_TxnRef');
        $transaction = null;
        $order = null;

        if ($vnpTxnRef) {
            $transaction = PaymentTransaction::where('provider', 'vnpay')
                ->where('provider_reference', $vnpTxnRef)
                ->with('order')
                ->first();

            $order = $transaction?->order;
        }

        $isValidSignature = $this->vnpayService->verifySignature($request->query());
        $vnpResponseCode = $request->query('vnp_ResponseCode');

        return view('payments.result', [
            'provider' => 'VNPAY',
            'order' => $order,
            'transaction' => $transaction,
            'isValidSignature' => $isValidSignature,
            'responseCode' => $vnpResponseCode,
        ]);
    }

    public function ipn(Request $request): JsonResponse
    {
        $inputData = $request->all();

        if (! $this->vnpayService->verifySignature($inputData)) {
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
        }

        $vnpTxnRef = $request->query('vnp_TxnRef') ?? ($inputData['vnp_TxnRef'] ?? null);
        $transaction = PaymentTransaction::where('provider', 'vnpay')
            ->where('provider_reference', $vnpTxnRef)
            ->with('order')
            ->first();

        if (! $transaction || ! $transaction->order) {
            return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
        }

        $incomingAmount = (float) ($request->query('vnp_Amount') ?? ($inputData['vnp_Amount'] ?? 0)) / 100;
        if (abs($incomingAmount - (float) $transaction->amount) > 0.01) {
            return response()->json(['RspCode' => '04', 'Message' => 'Invalid amount']);
        }

        if ($transaction->isSuccess() || $transaction->order->payment_status === 'paid') {
            return response()->json(['RspCode' => '02', 'Message' => 'Order already confirmed']);
        }

        DB::transaction(function () use ($request, $inputData, $transaction) {
            $responseCode = $request->query('vnp_ResponseCode') ?? ($inputData['vnp_ResponseCode'] ?? null);
            $transactionStatus = $request->query('vnp_TransactionStatus') ?? ($inputData['vnp_TransactionStatus'] ?? null);
            $transactionNo = $request->query('vnp_TransactionNo') ?? ($inputData['vnp_TransactionNo'] ?? null);

            if ($responseCode === '00' && $transactionStatus === '00') {
                $transaction->update([
                    'status' => 'success',
                    'provider_transaction_id' => $transactionNo,
                    'response_code' => $responseCode,
                    'paid_at' => now(),
                ]);
                $transaction->order->update(['payment_status' => 'paid']);
            } else {
                $transaction->update([
                    'status' => 'failed',
                    'response_code' => $responseCode,
                    'failed_at' => now(),
                ]);
            }
        });

        return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
    }
}
