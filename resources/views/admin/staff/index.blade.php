@extends('layouts.admin')

@section('title', 'Quản lý Nhân viên')
@section('page_title', 'Quản lý Nhân viên')

@section('content')
<div class="bg-surface rounded-xl border border-ui-border p-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <form action="{{ route('admin.staff.index') }}" method="GET" class="flex flex-wrap gap-2 w-full sm:w-auto">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm tên, email, sđt..." class="text-xs border border-ui-border rounded-lg p-2 outline-none w-56 bg-page text-heading">
            <select name="role" class="text-xs border border-ui-border rounded-lg p-2 outline-none bg-page text-heading">
                <option value="">Tất cả vai trò</option>
                <option value="staff" {{ request('role') == 'staff' ? 'selected' : '' }}>Nhân viên</option>
                <option value="manager" {{ request('role') == 'manager' ? 'selected' : '' }}>Quản lý</option>
            </select>
            <button type="submit" class="bg-primary text-primary-foreground text-xs px-3 py-2 rounded-lg hover:opacity-90">Lọc</button>
        </form>

        <a href="{{ route('admin.staff.create') }}" class="bg-primary hover:opacity-90 text-primary-foreground font-semibold text-xs px-4 py-2 rounded-lg transition whitespace-nowrap">
            + Thêm Nhân viên
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-page uppercase text-muted font-semibold border-b border-ui-border">
                <tr>
                    <th class="py-3 px-3">Nhân viên</th>
                    <th class="py-3 px-3">Email / SĐT</th>
                    <th class="py-3 px-3">Vai trò</th>
                    <th class="py-3 px-3">Đơn xử lý</th>
                    <th class="py-3 px-3">Trạng thái</th>
                    <th class="py-3 px-3 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ui-border text-body">
                @forelse($staff as $member)
                    <tr class="hover:bg-page">
                        <td class="py-3 px-3">
                            <div class="flex items-center gap-2.5">
                                <div class="size-8 rounded-full bg-primary text-primary-foreground grid place-items-center font-bold text-xs shrink-0">
                                    {{ mb_strtoupper(mb_substr($member->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="font-semibold text-heading">{{ $member->name }}</div>
                                    <span class="text-muted text-[11px]">{{ '@' . $member->username }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-3">
                            <div class="text-heading">{{ $member->email }}</div>
                            <span class="text-muted text-[11px]">{{ $member->phone ?? '—' }}</span>
                        </td>
                        <td class="py-3 px-3">
                            @if($member->role === 'manager')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-purple-100 text-purple-800">Quản lý</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-sky-100 text-sky-800">Nhân viên</span>
                            @endif
                        </td>
                        <td class="py-3 px-3 font-semibold">{{ $member->orders_count ?? 0 }}</td>
                        <td class="py-3 px-3">
                            @if($member->is_active)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800">Hoạt động</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-rose-100 text-rose-800">Tạm ngưng</span>
                            @endif
                        </td>
                        <td class="py-3 px-3 text-right space-x-2 whitespace-nowrap">
                            <form action="{{ route('admin.staff.toggle', $member) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-[11px] font-semibold {{ $member->is_active ? 'text-amber-700' : 'text-emerald-700' }} hover:underline">
                                    {{ $member->is_active ? 'Tạm ngưng' : 'Kích hoạt' }}
                                </button>
                            </form>
                            <a href="{{ route('admin.staff.edit', $member) }}" class="text-primary font-bold hover:underline">Sửa</a>
                            <form action="{{ route('admin.staff.destroy', $member) }}" method="POST" class="inline" onsubmit="return confirm('Xóa nhân viên này?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-rose-600 font-bold hover:underline">Xóa</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-muted">Chưa có nhân viên nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $staff->links() }}</div>
</div>
@endsection
