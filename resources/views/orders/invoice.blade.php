<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn #{{ $order->order_code }} - Nội thất Mộc An</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
        }
        .font-display {
            font-family: 'Playfair Display', Georgia, serif;
        }
        @media print {
            body {
                background: white !important;
                color: black !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .print-container {
                box-shadow: none !important;
                border: none !important;
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            @page {
                size: A4;
                margin: 15mm 12mm 15mm 12mm;
            }
        }
    </style>
</head>
<body class="py-8 px-4 sm:px-6">

    <!-- Top Action Bar (Screen Only) -->
    <div class="max-w-4xl mx-auto mb-6 flex items-center justify-between no-print">
        <a href="javascript:history.back()" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-gray-900 transition">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
            Quay lại
        </a>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="inline-flex items-center gap-2 rounded-xl bg-amber-900 px-5 py-2.5 text-sm font-bold text-white shadow-md hover:bg-amber-950 transition hover:shadow-lg">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                In / Lưu file PDF
            </button>
        </div>
    </div>

    <!-- Invoice Paper (A4 size styled) -->
    <div class="print-container max-w-4xl mx-auto bg-white rounded-2xl shadow-xl border border-gray-200 p-8 sm:p-12 relative overflow-hidden">
        
        <!-- Watermark -->
        <div class="absolute inset-0 flex items-center justify-center opacity-[0.03] pointer-events-none select-none">
            <span class="font-display text-9xl font-bold tracking-widest text-amber-950">MỘC AN</span>
        </div>

        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start border-b border-gray-200 pb-8 gap-6 relative z-10">
            <div>
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-xl bg-amber-900 text-white font-display text-xl font-bold">M</span>
                    <span class="font-display text-2xl font-bold tracking-tight text-gray-900">Mộc An Furniture</span>
                </div>
                <p class="mt-2 text-xs text-gray-500 max-w-sm leading-relaxed">
                    Công ty TNHH Sản xuất & Thương mại Nội thất Mộc An<br>
                    Showroom: 128 Nguyễn Trãi, Q. Thanh Xuân, TP. Hà Nội<br>
                    Hotline: 0901 234 567 &bull; Email: cskh@mocan.vn
                </p>
            </div>
            <div class="text-left sm:text-right">
                <h1 class="font-display text-2xl sm:text-3xl font-bold uppercase tracking-wider text-amber-900">Hóa Đơn Bán Hàng</h1>
                <p class="mt-1 text-xs text-gray-500">Kiêm phiếu bảo hành chính hãng 24 tháng</p>
                <div class="mt-3 inline-flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-1 text-xs font-mono font-bold text-gray-800">
                    <span>MÃ ĐƠN:</span>
                    <span class="text-amber-900">{{ $order->order_code }}</span>
                </div>
                <p class="mt-1.5 text-xs text-gray-500">Ngày lập: {{ $order->created_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>

        <!-- Customer & Order Meta -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 py-6 border-b border-gray-200 text-xs relative z-10">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Thông tin người mua</span>
                <h3 class="mt-1.5 text-sm font-bold text-gray-900">{{ $order->customer_name }}</h3>
                <p class="mt-1 text-gray-600">Số điện thoại: <span class="font-semibold">{{ $order->customer_phone }}</span></p>
                @if($order->customer_email)
                    <p class="text-gray-600">Email: {{ $order->customer_email }}</p>
                @endif
                <p class="mt-1 text-gray-600 leading-relaxed">Địa chỉ nhận hàng: <span class="font-semibold">{{ $order->shipping_address }}</span></p>
                @if($order->note)
                    <p class="mt-1 text-amber-800 italic">Ghi chú: "{{ $order->note }}"</p>
                @endif
            </div>
            <div class="sm:text-right">
                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Thông tin thanh toán & vận chuyển</span>
                <div class="mt-2 space-y-1.5">
                    <p class="text-gray-600">
                        Phương thức: 
                        <span class="font-bold text-gray-900">
                            @switch($order->payment_method)
                                @case('cod') Thanh toán khi nhận hàng (COD) @break
                                @case('vnpay') Cổng thanh toán VNPAY-QR @break
                                @case('momo') Ví điện tử MoMo @break
                                @case('bank_transfer') Chuyển khoản ngân hàng @break
                                @default {{ strtoupper($order->payment_method ?? 'COD') }}
                            @endswitch
                        </span>
                    </p>
                    <p class="text-gray-600">
                        Trạng thái thanh toán: 
                        <span class="inline-block px-2 py-0.5 rounded font-bold {{ $order->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                            {{ $order->payment_status === 'paid' ? 'ĐÃ THANH TOÁN' : 'CHƯA THANH TOÁN' }}
                        </span>
                    </p>
                    <p class="text-gray-600">
                        Trạng thái đơn hàng: 
                        <span class="font-bold text-gray-900">
                            @switch($order->order_status)
                                @case('pending') Chờ xác nhận @break
                                @case('confirmed') Đã xác nhận @break
                                @case('shipping') Đang vận chuyển @break
                                @case('completed') Giao hàng thành công @break
                                @case('canceled') Đã hủy @break
                                @default {{ ucfirst($order->order_status) }}
                            @endswitch
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="py-6 relative z-10">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-gray-300 text-gray-500 uppercase tracking-wider text-[10px]">
                        <th class="py-2.5 w-10 text-center font-bold">STT</th>
                        <th class="py-2.5 font-bold">Sản phẩm / Quy cách</th>
                        <th class="py-2.5 text-right font-bold w-24">Đơn giá</th>
                        <th class="py-2.5 text-center font-bold w-16">SL</th>
                        <th class="py-2.5 text-right font-bold w-28">Thành tiền</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($order->items as $idx => $item)
                        <tr>
                            <td class="py-3 text-center text-gray-400 font-mono">{{ $idx + 1 }}</td>
                            <td class="py-3 pr-4">
                                <span class="font-bold text-gray-900 text-sm block">{{ $item->product_name }}</span>
                                @if($item->variant_info)
                                    <span class="text-[11px] text-gray-500 font-medium">Quy cách: {{ $item->variant_info }}</span>
                                @elseif($item->variant)
                                    <span class="text-[11px] text-gray-500 font-medium">
                                        {{ $item->variant->color ? 'Màu: '.$item->variant->color : '' }}
                                        {{ $item->variant->size ? ' &bull; Kích thước: '.$item->variant->size : '' }}
                                        {{ $item->variant->material ? ' &bull; Chất liệu: '.$item->variant->material : '' }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 text-right font-mono text-gray-600">{{ number_format($item->price, 0, ',', '.') }}₫</td>
                            <td class="py-3 text-center font-mono font-bold text-gray-900">{{ $item->quantity }}</td>
                            <td class="py-3 text-right font-mono font-bold text-gray-900">{{ number_format($item->price * $item->quantity, 0, ',', '.') }}₫</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Summary & Totals -->
        <div class="border-t border-gray-200 pt-4 flex flex-col sm:flex-row justify-between items-start gap-6 relative z-10">
            <div class="max-w-md text-xs text-gray-500 leading-relaxed">
                <span class="font-bold text-gray-700 uppercase tracking-wider text-[10px] block mb-1">Chính sách bảo hành & bảo dưỡng</span>
                <p>Mộc An cam kết bảo hành khung sườn gỗ tự nhiên trong <strong>24 tháng</strong> kể từ ngày bàn giao. Hỗ trợ bảo dưỡng lau dầu gỗ và cân chỉnh ray trượt miễn phí định kỳ tại nhà.</p>
            </div>
            <div class="w-full sm:w-72 space-y-2 text-xs">
                @php
                    $subtotal = $order->items->sum(fn($i) => $i->price * $i->quantity);
                @endphp
                <div class="flex justify-between text-gray-600">
                    <span>Tạm tính tiền hàng:</span>
                    <span class="font-mono font-medium">{{ number_format($subtotal, 0, ',', '.') }}₫</span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>Phí vận chuyển & lắp đặt:</span>
                    <span class="font-mono font-medium">
                        {{ $order->shipping_fee > 0 ? number_format($order->shipping_fee, 0, ',', '.').'₫' : 'Miễn phí' }}
                    </span>
                </div>
                @if($order->discount_amount > 0)
                    <div class="flex justify-between text-emerald-700 font-semibold">
                        <span>Giảm giá ({{ $order->coupon_code }}):</span>
                        <span class="font-mono">-{{ number_format($order->discount_amount, 0, ',', '.') }}₫</span>
                    </div>
                @endif
                <div class="border-t border-gray-300 pt-2 flex justify-between items-baseline text-gray-900">
                    <span class="font-bold text-sm">TỔNG CỘNG:</span>
                    <span class="font-display text-xl font-bold text-amber-900 font-mono">
                        {{ number_format($order->total_price, 0, ',', '.') }}₫
                    </span>
                </div>
            </div>
        </div>

        <!-- Signatures & Stamp -->
        <div class="mt-12 pt-8 border-t border-gray-200 grid grid-cols-2 text-center text-xs relative z-10">
            <div>
                <span class="font-bold uppercase text-gray-700 tracking-wider">Người nhận hàng</span>
                <p class="mt-1 text-[11px] text-gray-400">(Ký và ghi rõ họ tên)</p>
                <div class="h-20"></div>
                <p class="font-semibold text-gray-800">{{ $order->customer_name }}</p>
            </div>
            <div class="relative">
                <span class="font-bold uppercase text-gray-700 tracking-wider">Đại diện Mộc An Furniture</span>
                <p class="mt-1 text-[11px] text-gray-400">(Ký, đóng dấu xác nhận)</p>
                <div class="h-20 flex items-center justify-center">
                    <!-- Digital Stamp Mock -->
                    <div class="border-2 border-red-600/80 rounded-full px-4 py-1 text-red-600 font-bold text-[10px] uppercase tracking-wider rotate-[-8deg] shadow-xs">
                        ĐÃ XÁC NHẬN BẢO HÀNH
                    </div>
                </div>
                <p class="font-semibold text-gray-800">Bộ phận KCS & Kho Vận</p>
            </div>
        </div>

        <!-- Footer Notice -->
        <div class="mt-8 pt-4 border-t border-gray-100 text-center text-[10px] text-gray-400">
            Hóa đơn điện tử được khởi tạo tự động từ hệ thống thương mại điện tử Mộc An &bull; Mã tra cứu: {{ $order->order_code }} &bull; https://mocan.vn
        </div>
    </div>

</body>
</html>
