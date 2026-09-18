<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Cập nhật trạng thái đơn hàng #{{ $order->order_code }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f7f6f2; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e0d8;">
        <div style="background-color: #4a3b32; color: #ffffff; padding: 24px; text-align: center;">
            <h1 style="margin: 0; font-size: 20px; letter-spacing: 1px;">MỘC AN</h1>
            <p style="margin: 4px 0 0; font-size: 12px; opacity: 0.8;">Nội thất & Không gian sống</p>
        </div>

        <div style="padding: 24px;">
            <h2 style="font-size: 16px; margin-top: 0; color: #222;">Cập nhật tiến độ đơn hàng</h2>
            <p style="font-size: 13px; color: #555;">Xin chào <strong>{{ $order->customer_name }}</strong>,</p>
            <p style="font-size: 13px; color: #555;">Đơn hàng <strong>#{{ $order->order_code }}</strong> của bạn vừa được cập nhật trạng thái mới:</p>

            <div style="background: #faf8f5; border-radius: 8px; padding: 16px; margin: 20px 0; border: 1px solid #ede8e1; text-align: center;">
                <span style="font-size: 11px; text-transform: uppercase; color: #777; letter-spacing: 1px;">Trạng thái hiện tại</span>
                <div style="font-size: 18px; font-weight: bold; color: #8b5a2b; margin-top: 4px;">
                    {{ $order->statusLabel() }}
                </div>
            </div>

            <div style="text-align: center; margin-top: 24px;">
                <a href="{{ route('orders.show', $order->order_code) }}" style="display: inline-block; padding: 10px 24px; background-color: #8b5a2b; color: #ffffff; text-decoration: none; border-radius: 8px; font-size: 13px; font-weight: bold;">
                    Theo dõi chi tiết đơn hàng
                </a>
            </div>
        </div>

        <div style="background: #faf8f5; padding: 16px; text-align: center; font-size: 11px; color: #999; border-top: 1px solid #ede8e1;">
            Mộc An - Tinh hoa gỗ Việt, nâng tầm không gian sống.<br>
            Hotline: 1900 6868 | Email: hotro@mocan.vn
        </div>
    </div>
</body>
</html>
