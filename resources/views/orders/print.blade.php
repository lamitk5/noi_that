<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hóa đơn #{{ $order->order_code }} — Mộc An Nội Thất</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 24px; color: #1a202c; background: #fff; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; line-height: 24px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #2b6cb0; padding-bottom: 20px; margin-bottom: 24px; }
        .brand h1 { margin: 0; font-size: 26px; color: #2b6cb0; }
        .brand p { margin: 4px 0 0 0; color: #718096; font-size: 13px; }
        .meta { text-align: right; }
        .meta h2 { margin: 0; font-size: 20px; color: #2d3748; }
        .meta p { margin: 4px 0 0 0; color: #718096; font-size: 13px; }
        .columns { display: flex; justify-content: space-between; margin-bottom: 24px; gap: 20px; }
        .column { flex: 1; background: #f7fafc; padding: 16px; border-radius: 6px; }
        .column h3 { margin-top: 0; margin-bottom: 8px; font-size: 13px; text-transform: uppercase; color: #4a5568; letter-spacing: 0.5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th { background: #edf2f7; color: #2d3748; text-align: left; padding: 10px 12px; font-size: 13px; text-transform: uppercase; }
        td { padding: 12px; border-bottom: 1px solid #edf2f7; font-size: 14px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals { margin-left: auto; width: 320px; margin-bottom: 30px; }
        .totals-row { display: flex; justify-content: space-between; padding: 6px 0; color: #4a5568; }
        .totals-row.grand-total { font-size: 16px; font-weight: bold; color: #2b6cb0; border-top: 2px solid #e2e8f0; padding-top: 10px; margin-top: 6px; }
        .footer { text-align: center; padding-top: 20px; border-top: 1px solid #e2e8f0; color: #a0aec0; font-size: 12px; }
        .print-btn { display: inline-block; background: #2b6cb0; color: #fff; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-bottom: 20px; cursor: pointer; border: none; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            .invoice-box { border: none; padding: 0; }
        }
    </style>
</head>
<body>
    <div style="max-width: 800px; margin: auto;" class="no-print">
        <button onclick="window.print()" class="print-btn">🖨️ In hóa đơn này</button>
    </div>

    <div class="invoice-box">
        <div class="header">
            <div class="brand">
                <h1>MỘC AN</h1>
                <p>Nội thất gỗ tự nhiên tinh tế & chuẩn mực</p>
                <p>Hotline: 1900 6868 — Email: cskh@mocan.vn</p>
            </div>
            <div class="meta">
                <h2>HÓA ĐƠN BÁN HÀNG</h2>
                <p><strong>Mã đơn:</strong> #{{ $order->order_code }}</p>
                <p><strong>Ngày đặt:</strong> {{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : '' }}</p>
                <p><strong>Trạng thái:</strong> {{ $order->statusLabel() }}</p>
            </div>
        </div>

        <div class="columns">
            <div class="column">
                <h3>Khách hàng & Nhận hàng</h3>
                <p style="margin: 0;"><strong>{{ $order->customer_name }}</strong></p>
                <p style="margin: 4px 0 0 0;">Điện thoại: {{ $order->customer_phone }}</p>
                @if($order->customer_email)
                    <p style="margin: 4px 0 0 0;">Email: {{ $order->customer_email }}</p>
                @endif
                <p style="margin: 4px 0 0 0;">Địa chỉ: {{ $order->shipping_address }}</p>
            </div>
            <div class="column">
                <h3>Thanh toán & Vận chuyển</h3>
                <p style="margin: 0;"><strong>Phương thức:</strong> {{ strtoupper($order->payment_method) }}</p>
                <p style="margin: 4px 0 0 0;"><strong>Trạng thái TT:</strong> {{ $order->payment_status === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán' }}</p>
                @if($order->shipping_carrier)
                    <p style="margin: 4px 0 0 0;">Đơn vị VC: {{ $order->shipping_carrier }}</p>
                    <p style="margin: 4px 0 0 0;">Mã vận đơn: {{ $order->tracking_code }}</p>
                @endif
                @if($order->note)
                    <p style="margin: 4px 0 0 0; color: #718096;"><em>Ghi chú: {{ $order->note }}</em></p>
                @endif
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 40px;" class="text-center">STT</th>
                    <th>Tên sản phẩm</th>
                    <th>Phân loại</th>
                    <th class="text-center" style="width: 70px;">SL</th>
                    <th class="text-right" style="width: 120px;">Đơn giá</th>
                    <th class="text-right" style="width: 130px;">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $idx => $item)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td><strong>{{ $item->product_name }}</strong></td>
                        <td>{{ $item->variant_info ?: 'Mặc định' }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">{{ number_format($item->price, 0, ',', '.') }} ₫</td>
                        <td class="text-right">{{ number_format($item->price * $item->quantity, 0, ',', '.') }} ₫</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            @php
                $itemSubtotal = $order->items->sum(fn($i) => $i->price * $i->quantity);
            @endphp
            <div class="totals-row">
                <span>Tạm tính hàng hóa:</span>
                <span>{{ number_format($itemSubtotal, 0, ',', '.') }} ₫</span>
            </div>
            <div class="totals-row">
                <span>Phí vận chuyển:</span>
                <span>{{ number_format($order->shipping_fee, 0, ',', '.') }} ₫</span>
            </div>
            @if($order->discount_amount > 0)
                <div class="totals-row" style="color: #38a169;">
                    <span>Giảm giá voucher:</span>
                    <span>-{{ number_format($order->discount_amount, 0, ',', '.') }} ₫</span>
                </div>
            @endif
            @if($order->points_discount > 0)
                <div class="totals-row" style="color: #38a169;">
                    <span>Điểm thưởng ({{ $order->points_used }} điểm):</span>
                    <span>-{{ number_format($order->points_discount, 0, ',', '.') }} ₫</span>
                </div>
            @endif
            <div class="totals-row grand-total">
                <span>TỔNG THANH TOÁN:</span>
                <span>{{ number_format($order->total_price, 0, ',', '.') }} ₫</span>
            </div>
        </div>

        <div class="footer">
            <p>Cảm ơn quý khách đã tin tưởng mua sắm tại Mộc An!</p>
            <p>Mọi thắc mắc về đơn hàng, quý khách vui lòng liên hệ hotline 1900 6868 hoặc gửi yêu cầu hỗ trợ qua website.</p>
        </div>
    </div>
</body>
</html>
