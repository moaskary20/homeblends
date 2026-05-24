<?php

return [
    'mode' => env('PAYPAL_MODE', 'sandbox'),
    'sandbox' => [
        'client_id' => env('PAYPAL_SANDBOX_CLIENT_ID', ''),
        'client_secret' => env('PAYPAL_SANDBOX_CLIENT_SECRET', ''),
    ],
    'live' => [
        'client_id' => env('PAYPAL_LIVE_CLIENT_ID', ''),
        'client_secret' => env('PAYPAL_LIVE_CLIENT_SECRET', ''),
    ],
    'payment_action' => 'Sale',
    'currency' => env('ECOMMERCE_CURRENCY', 'EGP'),
    'notify_url' => env('PAYPAL_NOTIFY_URL', ''),
    'locale' => 'ar_EG',
];
