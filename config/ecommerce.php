<?php

return [
    'currency' => env('ECOMMERCE_CURRENCY', 'EGP'),
    'locale' => env('ECOMMERCE_LOCALE', 'ar'),
    'supported_locales' => ['ar'],
    'supported_currencies' => ['EGP'],

    'loyalty' => [
        'earn_per_currency' => (int) env('LOYALTY_EARN_PER', 10),
        'earn_multiplier' => (float) env('LOYALTY_EARN_MULTIPLIER', 1),
        'point_value' => (float) env('LOYALTY_POINT_VALUE', 0.1),
        'expiry_months' => (int) env('LOYALTY_EXPIRY_MONTHS', 12),
        'min_redeem_points' => (int) env('LOYALTY_MIN_REDEEM', 10),
        'max_redeem_percent' => (int) env('LOYALTY_MAX_REDEEM_PERCENT', 50),
    ],

    'payments' => [
        'local' => [
            'api_url' => env('LOCAL_PAYMENT_API_URL'),
            'api_key' => env('LOCAL_PAYMENT_API_KEY'),
        ],
    ],

    'cache' => [
        'product_ttl' => 3600,
        'category_ttl' => 7200,
    ],

    'compare' => [
        'max_items' => 4,
    ],
];
