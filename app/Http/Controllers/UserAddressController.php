<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserAddressController extends Controller
{
    public function index(Request $request): View
    {
        $addresses = $request->user()->addresses()->orderByDesc('is_default')->latest()->get();

        return view('account.addresses.index', compact('addresses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\s().-]+$/'],
            'address_line' => ['required', 'string', 'max:255'],
            'ward' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
        ], [
            'recipient_name.required' => 'Vui lòng nhập tên người nhận.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
            'address_line.required' => 'Vui lòng nhập số nhà, tên đường.',
            'city.required' => 'Vui lòng nhập tỉnh / thành phố.',
        ]);

        $user = $request->user();
        $isDefault = $request->boolean('is_default');

        // If this is the user's first address, make it default automatically
        if ($user->addresses()->count() === 0) {
            $isDefault = true;
        }

        if ($isDefault) {
            $user->addresses()->update(['is_default' => false]);
        }

        $user->addresses()->create([
            'recipient_name' => $validated['recipient_name'],
            'phone' => $validated['phone'],
            'address_line' => $validated['address_line'],
            'ward' => $validated['ward'] ?? null,
            'district' => $validated['district'] ?? null,
            'city' => $validated['city'],
            'is_default' => $isDefault,
        ]);

        return redirect()->route('account.addresses.index')->with('success', 'Đã lưu địa chỉ nhận hàng thành công.');
    }

    public function update(Request $request, UserAddress $address): RedirectResponse
    {
        if ($address->user_id !== $request->user()->id) {
            abort(403, 'Bạn không có quyền sửa địa chỉ này.');
        }

        $validated = $request->validate([
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\s().-]+$/'],
            'address_line' => ['required', 'string', 'max:255'],
            'ward' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $isDefault = $request->boolean('is_default');
        if ($isDefault) {
            $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update([
            'recipient_name' => $validated['recipient_name'],
            'phone' => $validated['phone'],
            'address_line' => $validated['address_line'],
            'ward' => $validated['ward'] ?? null,
            'district' => $validated['district'] ?? null,
            'city' => $validated['city'],
            'is_default' => $isDefault,
        ]);

        return redirect()->route('account.addresses.index')->with('success', 'Đã cập nhật địa chỉ thành công.');
    }

    public function destroy(Request $request, UserAddress $address): RedirectResponse
    {
        if ($address->user_id !== $request->user()->id) {
            abort(403, 'Bạn không có quyền xóa địa chỉ này.');
        }

        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $firstRemaining = $request->user()->addresses()->first();
            if ($firstRemaining) {
                $firstRemaining->update(['is_default' => true]);
            }
        }

        return redirect()->route('account.addresses.index')->with('success', 'Đã xóa địa chỉ thành công.');
    }

    public function setDefault(Request $request, UserAddress $address): RedirectResponse
    {
        if ($address->user_id !== $request->user()->id) {
            abort(403, 'Bạn không có quyền thao tác trên địa chỉ này.');
        }

        $request->user()->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return redirect()->route('account.addresses.index')->with('success', 'Đã đặt địa chỉ mặc định thành công.');
    }
}
