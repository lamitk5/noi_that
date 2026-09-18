<?php

return [
    'shipping_fee' => 0,

    'bank_transfer' => [
        'bank_name' => env('BANK_NAME', 'Ngân hàng TMCP Ngoại thương Việt Nam (Vietcombank)'),
        'account_number' => env('BANK_ACCOUNT_NUMBER', '1234567890'),
        'account_holder' => env('BANK_ACCOUNT_HOLDER', 'CONG TY NOI THAT MOC AN'),
    ],
];
