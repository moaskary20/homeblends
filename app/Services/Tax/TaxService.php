<?php

namespace App\Services\Tax;

use App\Models\TaxRate;

class TaxService
{
    public function calculate(float $amount, string $country = 'EG'): float
    {
        $rate = TaxRate::query()
            ->where('country', $country)
            ->where('is_active', true)
            ->first();

        if (! $rate) {
            return 0;
        }

        return round($amount * ($rate->rate / 100), 2);
    }
}
