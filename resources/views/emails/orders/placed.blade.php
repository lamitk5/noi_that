<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Xác nhận đơn hàng #{{ $order->order_code }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f7f6f2; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e0d8;">
        <div style="background-color: #4a3b32; color: #ffffff; padding: 24px; text-align: center;">
            <h1 style="margin: 0; font-size: 20px; letter-spacing: 1px;">MỘC AN</h1>
            <p style="margin: 4px 0 0; font-size: 12px; opacity: 0.8;">Nội thất & Không gian sống</p>
        </div>

        <div style="padding: 24px;">
            <h2 style="font-size: 16px; margin-top: 0; color: #222;">Cảm ơn bạn đã đặt hàng tại Mộc An!</h2>
            <p style="font-size: 13px; color: #555;">Xin chào <strong>{{ $order->customer_name }}</strong>, đơn hàng <strong>#{{ $order->order_code }}</strong> của bạn đã được tiếp nhận thành công vào hệ thống.</p>

            <div style="background: #faf8f5; border-radius: 8px; padding: 16px; margin: 20px 0; border: 1px solid #ede8e1;">
                <h3 style="margin-top: 0; font-size: 13px; text-transform: uppercase; color: #777;">Thông tin đơn hàng</h3>
                <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 4px 0; color: #777;">Mã đơn hàng:</td>
                        <td style="padding: 4px 0; font-weight: bold; text-align: right;">#{{ $order->order_code }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; color: #777;">Người nhận:</td>
                        <td style="padding: 4px 0; text-align: right;">{{ $order->customer_name }} ({{ $order->customer_phone }})</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; color: #777;">Địa chỉ giao:</td>
                        <td style="padding: 4px 0; text-align: right;">{{ $order->shipping_address }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; color: #777;">Phương thức thanh toán:</td>
                        <td style="padding: 4px 0; text-align: right; text-transform: uppercase;">{{ $order->payment_method }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0 0; font-weight: bold; font-size: 14px; border-top: 1px dashed #ccc;">Tổng thanh toán:</td>
                        <td style="padding: 8px 0 0; font-weight: bold; font-size: 14px; color: #8b5a2b; text-align: right; border-top: 1px dashed #ccc;">
                            {{ number_format($order->total_price, 0, ',', '.') }}đ
                        </td>
                    </tr>
                </table>
            </div>

            <p style="font-size: 12px; color: #777;">Mộc An sẽ liên hệ với bạn để xác nhận đơn hàng và lịch giao lắp đặt.</p>
        </div>

        <div style="background: #faf8f5; padding: 16px; text-align: center; font-size: 11px; color: #999; border-top: 1px solid #ede8e1;">
            Mộc An - Tinh hoa gỗ Việt, nâng tầm không gian sống.<br>
            Hotline: 1900 6868 | Email: hotro@mocan.vn
        </div>
    </div>
</body>
</html>
