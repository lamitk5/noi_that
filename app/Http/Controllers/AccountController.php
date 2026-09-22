<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.index', [
            'user' => $request->user(),
        ]);
    }

    public function edit(Request $request): View
    {
        return view('account.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        return redirect()->route('account.index')->with('status', 'Thông tin cá nhân đã được cập nhật thành công.');
    }

    public function loyalty(Request $request): View
    {
        $user = $request->user();
        $user->recalculateTier();
        $transactions = $user->loyaltyTransactions()->take(30)->get();

        return view('account.loyalty', compact('user', 'transactions'));
    }

    public function appearance(Request $request): View
    {
        $user = $request->user();
        $settings = $user->appearance_settings ?? [];

        return view('account.appearance', [
            'user' => $user,
            'settings' => array_merge([
                'theme' => 'wood',
                'font_scale' => 'base',
                'density' => 'comfortable',
                'reduced_motion' => '0',
            ], $settings),
        ]);
    }

    public function updateAppearance(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'theme' => ['required', 'string', 'in:moss,wood,cream,blue,black'],
            'font_scale' => ['required', 'string', 'in:sm,base,lg'],
            'density' => ['required', 'string', 'in:compact,comfortable'],
            'reduced_motion' => ['required', 'in:0,1'],
        ]);

        $user = $request->user();
        $user->update([
            'appearance_settings' => $validated,
        ]);

        return back()->with('status', 'Tùy chọn giao diện cá nhân đã được lưu thành công!');
    }

    public function resetAppearance(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        $user->update([
            'appearance_settings' => null,
        ]);

        return back()->with('status', 'Đã khôi phục cài đặt giao diện về mặc định!');
    }
}