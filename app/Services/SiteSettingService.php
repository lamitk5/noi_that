<?php

namespace App\Services;

use App\Models\SiteSetting;

class SiteSettingService
{
    /**
     * Get a setting by key with fallback default.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return SiteSetting::get($key, $default);
    }

    /**
     * Set a setting value and invalidate cache.
     */
    public function set(string $key, mixed $value, string $group = 'general', string $type = 'string'): SiteSetting
    {
        return SiteSetting::set($key, $value, $group, $type);
    }

    /**
     * Get all settings mapped by key.
     */
    public function all(): array
    {
        return SiteSetting::getAllSettings();
    }

    /**
     * Clear all cached settings.
     */
    public function clearCache(): void
    {
        SiteSetting::clearCache();
    }
}
