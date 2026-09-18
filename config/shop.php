<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Shop Configuration
    |--------------------------------------------------------------------------
    |
    | Low stock warning threshold across the storefront and admin panels.
    |
    */
    'low_stock_threshold' => (int) env('SHOP_LOW_STOCK_THRESHOLD', 5),
];
