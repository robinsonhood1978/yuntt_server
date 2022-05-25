<?php

return [
    'api_key'       => env('LATIPAY_API_KEY', ''),
    'user_id'       => env('LATIPAY_USER_ID', ''),
    'wallet_id_nzd' => env('LATIPAY_WALLET_ID_NZD', ''),
    'wallet_id_aud' => env('LATIPAY_WALLET_ID_AUD', ''),
    'wallet_id_cny' => env('LATIPAY_WALLET_ID_CNY', ''),
    'version'       => env('LATIPAY_VERSION', '2.0'),
];
