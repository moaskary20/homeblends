<?php

return [
    'cookie_name' => 'affiliate_ref',
    'cookie_days' => (int) env('AFFILIATE_COOKIE_DAYS', 30),
    'default_commission_rate' => (float) env('AFFILIATE_COMMISSION_RATE', 10),
    'min_payout_amount' => (float) env('AFFILIATE_MIN_PAYOUT', 100),
    'commission_on' => env('AFFILIATE_COMMISSION_ON', 'delivered'),
    'auto_approve_applications' => (bool) env('AFFILIATE_AUTO_APPROVE', false),
];
