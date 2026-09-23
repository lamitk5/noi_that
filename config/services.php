<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

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
        'tmn_code' => env('VNPAY_TMN_CODE', 'CGXZLS0Z'),
        'hash_secret' => env('VNPAY_HASH_SECRET', 'XNBCJFAKAZQSGTARRLGCHVZWCIOIGSHN'),
    ],

    'momo' => [
        'url' => env('MOMO_PAYMENT_URL', 'https://test-payment.momo.vn/v2/gateway/api/create'),
        'partner_code' => env('MOMO_PARTNER_CODE', 'MOMO'),
        'access_key' => env('MOMO_ACCESS_KEY', 'F8BBA842ECF85'),
        'secret_key' => env('MOMO_SECRET_KEY', 'K951B6PE1wa80fS6lGXDOjUhtgYXlyQ3'),
    ],

    'ghn' => [
        'url' => env('GHN_API_URL', 'https://dev-online-gateway.ghn.vn/shiip/public-api/'),
        'token' => env('GHN_TOKEN', '8440538a-989d-11ee-a6e6-e60958111f48'),
        'shop_id' => (int) env('GHN_SHOP_ID', 190566),
        'from_district_id' => (int) env('GHN_FROM_DISTRICT_ID', 1442),
        'from_ward_code' => (string) env('GHN_FROM_WARD_CODE', '20101'),
        'auto_create_order' => (bool) env('GHN_AUTO_CREATE_ORDER', true),
        'default_fee' => (float) env('GHN_DEFAULT_FEE', 30000),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_REDIRECT_URI', '/auth/github/callback'),
    ],

];
