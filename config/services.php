<?php

return [

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'vnpay' => [
        'url' => env('VNPAY_PAYMENT_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
        'tmn_code' => env('VNPAY_TMN_CODE'),
        'hash_secret' => env('VNPAY_HASH_SECRET'),
    ],

    'momo' => [
        'url' => env('MOMO_PAYMENT_URL', 'https://test-payment.momo.vn/v2/gateway/api/create'),
        'partner_code' => env('MOMO_PARTNER_CODE'),
        'access_key' => env('MOMO_ACCESS_KEY'),
        'secret_key' => env('MOMO_SECRET_KEY'),
    ],

    'ghn' => [
        'url' => env('GHN_API_URL', 'https://dev-online-gateway.ghn.vn/shiip/public-api/'),
        'token' => env('GHN_TOKEN', 'd3555875-a814-11f1-a973-aee5264794df'),
        'shop_id' => (int) env('GHN_SHOP_ID', 216783),
        'from_district_id' => (int) env('GHN_FROM_DISTRICT_ID', 1442),
        'from_ward_code' => (string) env('GHN_FROM_WARD_CODE', '20101'),
        'auto_create_order' => (bool) env('GHN_AUTO_CREATE_ORDER', true),
        'default_fee' => (float) env('GHN_DEFAULT_FEE', 30000),
    ],

];
