@extends('layouts.admin')

@section('title', 'Quáº£n LÃ½ ÄÆ¡n HÃ ng Ná»™i Tháº¥t')
@section('page_title', 'Danh SÃ¡ch ÄÆ¡n Äáº·t HÃ ng')

@section('content')
<div class="bg-white rounded-xl border shadow-sm p-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <form action="{{ route('admin.orders.index') }}" method="GET" class="flex flex-wrap gap-2 w-full sm:w-auto">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="MÃ£ Ä‘Æ¡n, tÃªn, sÄ‘t khÃ¡ch..." class="text-xs border rounded-lg p-2 outline-none w-56">
            <select name="status" class="text-xs border rounded-lg p-2 outline-none">
                <option value="">Táº¥t cáº£ tráº¡ng thÃ¡i</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chá» xá»­ lÃ½</option>
                <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>ÄÃ£ xÃ¡c nháº­n</option>
                <option value="shipping" {{ request('status') == 'shipping' ? 'selected' : '' }}>Äang giao hÃ ng</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>HoÃ n thÃ nh</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ÄÃ£ há»§y</option>
            </select>
            <button type="submit" class="bg-gray-800 text-white text-xs px-3 py-2 rounded-lg hover:bg-gray-900">
                <i class="fa-solid fa-magnifying-glass"></i> Lá»c
            </button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-gray-50 uppercase text-gray-500 font-semibold border-b">
                <tr>
                    <th class="py-3 px-3">MÃ£ Ä‘Æ¡n</th>
                    <th class="py-3 px-3">KhÃ¡ch hÃ ng</th>
                    <th class="py-3 px-3">Sá»‘ Ä‘iá»‡n thoáº¡i</th>
                    <th class="py-3 px-3">Tá»•ng tiá»n</th>
                    <th class="py-3 px-3">Thanh toÃ¡n</th>
                    <th class="py-3 px-3">Tráº¡ng thÃ¡i Ä‘Æ¡n</th>
                    <th class="py-3 px-3">NgÃ y Ä‘áº·t</th>
                    <th class="py-3 px-3 text-right">Thao tÃ¡c</th>
                </tr>
            </thead>
            <tbody class="divide-y text-gray-700">
                @foreach($orders as $order)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-3 font-mono font-bold text-amber-900">{{ $order->order_number }}</td>
                        <td class="py-3 px-3 font-semibold">{{ $order->customer_name }}</td>
                        <td class="py-3 px-3">{{ $order->customer_phone }}</td>
                        <td class="py-3 px-3 font-extrabold text-amber-950">{{ number_format($order->total_amount, 0, ',', '.') }} Ä‘</td>
                        <td class="py-3 px-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $order->payment_status == 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $order->payment_status_label }}
                            </span>
                        </td>
                        <td class="py-3 px-3">
                            <span class="px-2.5 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-800">
                                {{ $order->order_status_label }}
                            </span>
                        </td>
                        <td class="py-3 px-3 text-gray-400">{{ $order->created_at->format('d/m/Y') }}</td>
                        <td class="py-3 px-3 text-right">
                            <a href="{{ route('admin.orders.show', $order) }}" class="text-amber-800 font-bold hover:underline">Chi tiáº¿t</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $orders->links() }}
    </div>
</div>
@endsection

