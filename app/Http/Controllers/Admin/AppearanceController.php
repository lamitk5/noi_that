<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppearanceController extends Controller
{
    /**
     * Default appearance configurations.
     */
    public const DEFAULTS = [
        'site_name' => 'Mộc An',
        'site_tagline' => 'Nội Thất Gỗ Tự Nhiên & Đương Đại',
        'site_hotline' => '1900 6868',
        'site_email' => 'contact@mocan.vn',
        'site_address' => 'Tòa nhà Mộc An, 123 Đường Nội Thất, Hà Nội',
        'promo_bar_enabled' => '1',
        'promo_bar_text' => 'Miễn phí giao hàng & lắp đặt tận nơi cho đơn hàng từ 5.000.000₫ · Hotline hỗ trợ: 1900 6868',
        'default_theme' => 'wood',
        'font_primary' => 'Be Vietnam Pro',
        'font_heading' => 'Playfair Display',
        'hero_title' => 'Tôn vinh vẻ đẹp tự nhiên trong từng thớ gỗ',
        'hero_subtitle' => 'Khám phá bộ sưu tập nội thất thủ công tinh xảo, giao hòa giữa vẻ đẹp mộc mạc và công năng tiện nghi cho không gian sống hiện đại.',
        'show_hero' => '1',
        'show_trust_badges' => '1',
        'show_categories' => '1',
        'show_best_sellers' => '1',
        'show_testimonials' => '1',
        'footer_copyright' => '© 2026 Mộc An Woodworks. Tinh hoa nội thất Việt.',
        'social_facebook' => 'https://facebook.com',
        'social_instagram' => 'https://instagram.com',
        'social_youtube' => 'https://youtube.com',
    ];

    public function index(): View
    {
        $settings = [];
        foreach (self::DEFAULTS as $key => $default) {
            $settings[$key] = SiteSetting::get($key, $default);
        }

        return view('admin.settings.appearance', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name' => 'nullable|string|max:100',
            'site_tagline' => 'nullable|string|max:255',
            'site_hotline' => 'nullable|string|max:50',
            'site_email' => 'nullable|email|max:100',
            'site_address' => 'nullable|string|max:255',
            'promo_bar_enabled' => 'nullable|in:0,1',
            'promo_bar_text' => 'nullable|string|max:255',
            'default_theme' => 'nullable|string|in:moss,wood,cream,blue,black',
            'font_primary' => 'nullable|string|max:100',
            'font_heading' => 'nullable|string|max:100',
            'hero_title' => 'nullable|string|max:255',
            'hero_subtitle' => 'nullable|string|max:500',
            'show_hero' => 'nullable|in:0,1',
            'show_trust_badges' => 'nullable|in:0,1',
            'show_categories' => 'nullable|in:0,1',
            'show_best_sellers' => 'nullable|in:0,1',
            'show_testimonials' => 'nullable|in:0,1',
            'footer_copyright' => 'nullable|string|max:255',
            'social_facebook' => 'nullable|url|max:255',
            'social_instagram' => 'nullable|url|max:255',
            'social_youtube' => 'nullable|url|max:255',
        ]);

        foreach (self::DEFAULTS as $key => $default) {
            $val = $validated[$key] ?? (in_array($key, ['promo_bar_enabled', 'show_hero', 'show_trust_badges', 'show_categories', 'show_best_sellers', 'show_testimonials']) ? '0' : $default);
            SiteSetting::set($key, $val, 'appearance');
        }

        return back()->with('status', 'Cài đặt giao diện đã được cập nhật thành công!');
    }

    public function resetDefaults(): RedirectResponse
    {
        foreach (self::DEFAULTS as $key => $default) {
            SiteSetting::set($key, $default, 'appearance');
        }

        return back()->with('status', 'Đã khôi phục cấu hình giao diện về giá trị mặc định!');
    }
}
