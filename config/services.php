<?php

return [

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'brevo' => [
        'key' => env('BREVO_API_KEY'),
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
        'url'         => env('VNPAY_PAYMENT_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
        'tmn_code'    => env('VNPAY_TMN_CODE'),
        'hash_secret' => env('VNPAY_HASH_SECRET'),
        'mock'        => env('VNPAY_MOCK', false),
    ],

    'momo' => [
        'url'          => env('MOMO_PAYMENT_URL', 'https://test-payment.momo.vn/v2/gateway/api/create'),
        'partner_code' => env('MOMO_PARTNER_CODE'),
        'access_key'   => env('MOMO_ACCESS_KEY'),
        'secret_key'   => env('MOMO_SECRET_KEY'),
        'request_type' => env('MOMO_REQUEST_TYPE', 'payWithATM'),
        'mock'         => env('MOMO_MOCK', false),
    ],

    'ghn' => [
        'url' => env('GHN_API_URL', 'https://dev-online-gateway.ghn.vn/shiip/public-api/'),
        'token' => env('GHN_TOKEN'),
        'shop_id' => (int) env('GHN_SHOP_ID', 0),
        'from_district_id' => (int) env('GHN_FROM_DISTRICT_ID', 1442),
        'from_ward_code' => (string) env('GHN_FROM_WARD_CODE', '20101'),
        'auto_create_order' => (bool) env('GHN_AUTO_CREATE_ORDER', true),
        'default_fee' => (float) env('GHN_DEFAULT_FEE', 30000),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/auth/google/callback'),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.8-flash'),
        'endpoint' => env('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 20),
    ],

    'sms' => [
        'provider' => env('SMS_PROVIDER'),
        'esms' => [
            'api_key' => env('ESMS_API_KEY'),
            'secret_key' => env('ESMS_SECRET_KEY'),
            'brandname' => env('ESMS_BRANDNAME', 'Baotrimang'),
        ],
        'speedsms' => [
            'access_token' => env('SPEEDSMS_ACCESS_TOKEN'),
            'sender' => env('SPEEDSMS_SENDER', ''),
        ],
        'twilio' => [
            'sid' => env('TWILIO_SID'),
            'token' => env('TWILIO_AUTH_TOKEN'),
            'from' => env('TWILIO_FROM'),
        ],
    ],

];
