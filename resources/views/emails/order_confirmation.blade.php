<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận đơn hàng #{{ $order->order_code }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f7f5f0;
            color: #2c2523;
            margin: 0;
            padding: 24px;
            line-height: 1.5;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #e8e3d9;
        }
        .header {
            background-color: #4a3525;
            color: #ffffff;
            padding: 32px 28px;
            text-align: center;
        }
        .header h1 {
            margin: 0 0 6px 0;
            font-size: 24px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .header p {
            margin: 0;
            font-size: 13px;
            color: #d1b89d;
        }
        .content {
            padding: 32px 28px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .order-info-box {
            background-color: #fdfbf7;
            border: 1px solid #eee7db;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 24px;
            font-size: 13px;
        }
        .order-info-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .order-info-box td {
            padding: 4px 0;
        }
        .table-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
            font-size: 13px;
        }
        .table-items th {
            text-align: left;
            padding: 10px 8px;
            border-bottom: 2px solid #e8e3d9;
            color: #7d6e65;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 1px;
        }
        .table-items td {
            padding: 12px 8px;
            border-bottom: 1px solid #f0ece1;
        }
        .total-section {
            margin-top: 16px;
            border-top: 2px solid #4a3525;
            padding-top: 16px;
            text-align: right;
            font-size: 14px;
        }
        .total-amount {
            font-size: 20px;
            font-weight: bold;
            color: #8c4a27;
        }
        .cta-btn {
            display: inline-block;
            background-color: #8c4a27;
            color: #ffffff !important;
            text-decoration: none;
            font-weight: bold;
            font-size: 13px;
            padding: 12px 24px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .footer {
            background-color: #f4f0e8;
            padding: 24px 28px;
            text-align: center;
            font-size: 11px;
            color: #8b7e74;
            border-top: 1px solid #e8e3d9;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>MỘC AN FURNITURE</h1>
            <p>Kiến tạo không gian sống an yên & tinh tế</p>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Xin chào <strong>{{ $order->customer_name }}</strong>,
            </div>
            <p style="font-size: 14px; color: #554a43; margin-bottom: 20px;">
                Cảm ơn quý khách đã tin chọn sản phẩm nội thất tại <strong>Mộc An</strong>. Đơn hàng của quý khách đã được tiếp nhận thành công và đang được xưởng chuẩn bị chu đáo.
            </p>

            <!-- Order Info Box -->
            <div class="order-info-box">
                <table>
                    <tr>
                        <td style="color: #7d6e65; width: 40%;">Mã đơn hàng:</td>
                        <td><strong>#{{ $order->order_code }}</strong></td>
                    </tr>
                    <tr>
                        <td style="color: #7d6e65;">Ngày đặt:</td>
                        <td>{{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : date('d/m/Y H:i') }}</td>
                    </tr>
                    <tr>
                        <td style="color: #7d6e65;">Số điện thoại:</td>
                        <td>{{ $order->customer_phone }}</td>
                    </tr>
                    <tr>
                        <td style="color: #7d6e65;">Địa chỉ giao hàng:</td>
                        <td>{{ $order->shipping_address }}</td>
                    </tr>
                    <tr>
                        <td style="color: #7d6e65;">Phương thức thanh toán:</td>
                        <td style="text-transform: uppercase;"><strong>{{ $order->payment_method }}</strong> ({{ $order->payment_status === 'paid' ? 'Đã thanh toán' : 'Chờ thanh toán' }})</td>
                    </tr>
                </table>
            </div>

            <!-- Items Table -->
            <table class="table-items">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th style="text-align: center; width: 60px;">SL</th>
                        <th style="text-align: right; width: 110px;">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->product_name }}</strong>
                                @if ($item->variant_info)
                                    <div style="font-size: 11px; color: #888;">Quy cách: {{ $item->variant_info }}</div>
                                @endif
                            </td>
                            <td style="text-align: center;">{{ $item->quantity }}</td>
                            <td style="text-align: right; font-weight: bold;">{{ number_format($item->price * $item->quantity, 0, ',', '.') }}₫</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Totals -->
            <div class="total-section">
                @if ($order->discount_amount > 0)
                    <div style="color: #059669; margin-bottom: 4px;">
                        Giảm giá khuyến mại: -{{ number_format($order->discount_amount, 0, ',', '.') }}₫
                    </div>
                @endif
                <div style="color: #554a43; margin-bottom: 6px;">
                    Phí vận chuyển & lắp đặt: {{ $order->shipping_fee > 0 ? number_format($order->shipping_fee, 0, ',', '.') . '₫' : 'Miễn phí' }}
                </div>
                <div>
                    Tổng giá trị thanh toán: <span class="total-amount">{{ number_format($order->total_price, 0, ',', '.') }}₫</span>
                </div>
            </div>

            <!-- CTA Button -->
            <div style="text-align: center; margin-top: 28px;">
                <a href="{{ route('orders.invoice', $order->order_code) }}" class="cta-btn" target="_blank">
                    Xem & Tải Hóa Đơn PDF (A4)
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="margin: 0 0 6px 0;"><strong>Nội Thất Mộc An</strong> · Hotline tư vấn: 1900 6868 · Email: support@mocan.vn</p>
            <p style="margin: 0;">Mọi sản phẩm gỗ tự nhiên đều được cam kết bảo hành chính hãng 24 tháng và bảo trì trọn đời.</p>
        </div>
    </div>
</body>
</html>
