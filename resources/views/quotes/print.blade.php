<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo giá {{ $projectName }} - Nội thất Mộc An</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f3f4f6; color: #1f2937; }
        .font-display { font-family: 'Playfair Display', Georgia, serif; }
        @media print {
            body { background: white !important; padding: 0 !important; }
            .no-print { display: none !important; }
            .print-container { box-shadow: none !important; border: none !important; margin: 0 !important; max-width: 100% !important; }
            @page { size: A4; margin: 15mm 12mm; }
        }
    </style>
</head>
<body class="px-4 py-8">
    <div class="mx-auto mb-6 flex max-w-4xl items-center justify-between no-print">
        <a href="{{ route('quotes.create') }}" class="text-sm font-semibold text-gray-600">← Sửa báo giá</a>
        <button onclick="window.print()" class="rounded-xl bg-amber-900 px-5 py-2.5 text-sm font-bold text-white">In / Lưu file PDF</button>
    </div>
    <div class="print-container mx-auto max-w-4xl rounded-2xl border border-gray-200 bg-white p-8 shadow-xl sm:p-12">
        <div class="flex flex-col justify-between gap-6 border-b border-gray-200 pb-8 sm:flex-row">
            <div>
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-xl bg-amber-900 font-display text-xl font-bold text-white">M</span>
                    <span class="font-display text-2xl font-bold">Mộc An Furniture</span>
                </div>
                <p class="mt-2 max-w-sm text-xs leading-relaxed text-gray-500">
                    Showroom: 128 Nguyễn Trãi, Q. Thanh Xuân, TP. Hà Nội<br>
                    Hotline: 0901 234 567 · Email: cskh@mocan.vn
                </p>
            </div>
            <div class="sm:text-right">
                <h1 class="font-display text-3xl font-bold uppercase tracking-wider text-amber-900">Bảng báo giá</h1>
                <p class="mt-2 text-sm font-bold text-gray-900">{{ $projectName }}</p>
                <p class="text-xs text-gray-500">Ngày lập: {{ $quotedAt->format('d/m/Y H:i') }}</p>
            </div>
        </div>
        <div class="border-b border-gray-200 py-4 text-xs text-gray-600">
            <p>Người nhận: <strong class="text-gray-900">{{ $contactName }}</strong></p>
            <p>Điện thoại: {{ $phone }} @if($email) · {{ $email }} @endif</p>
        </div>
        <table class="mt-6 w-full text-left text-xs">
            <thead>
                <tr class="border-b border-gray-300 text-[10px] uppercase tracking-wider text-gray-500">
                    <th class="py-2">Sản phẩm</th>
                    <th class="py-2">Kích thước</th>
                    <th class="py-2 text-right">Đơn giá</th>
                    <th class="py-2 text-center">SL</th>
                    <th class="py-2 text-right">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($lines as $line)
                    <tr class="border-b border-gray-100">
                        <td class="py-3">
                            <div class="flex items-center gap-3">
                                <img src="{{ $line['product']->primary_image_url }}" alt="" class="size-12 rounded-lg object-cover">
                                <span class="font-semibold text-gray-900">{{ $line['product']->name }}</span>
                            </div>
                        </td>
                        <td class="py-3 text-gray-500">{{ \App\Support\FurnitureGlb::displaySize($line['product']->name, $line['product']->dimensions) }}</td>
                        <td class="py-3 text-right">{{ number_format($line['price'], 0, ',', '.') }}₫</td>
                        <td class="py-3 text-center">{{ $line['quantity'] }}</td>
                        <td class="py-3 text-right font-semibold">{{ number_format($line['line'], 0, ',', '.') }}₫</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-6 space-y-1 text-right text-sm">
            <p>Tạm tính: {{ number_format($subtotal, 0, ',', '.') }}₫</p>
            <p>Chiết khấu {{ rtrim(rtrim(number_format($discount, 1, '.', ''), '0'), '.') }}%: −{{ number_format($discountAmount, 0, ',', '.') }}₫</p>
            <p class="font-display text-xl font-bold text-amber-900">Tổng: {{ number_format($total, 0, ',', '.') }}₫</p>
        </div>
        <p class="mt-8 text-[11px] text-gray-400">Báo giá có hiệu lực 7 ngày. Giá đã gồm VAT, chưa gồm phí vận chuyển và lắp đặt.</p>
    </div>
</body>
</html>
