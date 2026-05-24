<?php

namespace App\Services\Shipping;

use App\Enums\ShippingRateType;
use App\Models\FreeShippingRule;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\Support\Collection;

class ShippingService
{
    public function calculate(
        int $shippingRateId,
        float $subtotal,
        float $weight = 0,
        ?string $country = 'EG',
    ): array {
        $rate = ShippingRate::query()
            ->with('zone')
            ->where('is_active', true)
            ->findOrFail($shippingRateId);

        $zone = $rate->zone;
        if ($zone && ! $zone->is_active) {
            throw new \InvalidArgumentException(__('ecommerce.shipping_zone_inactive'));
        }

        if ($country && $zone && ! $this->zoneCoversCountry($zone, $country)) {
            throw new \InvalidArgumentException(__('ecommerce.shipping_not_available_country'));
        }

        if (! $this->rateApplies($rate, $subtotal, $weight)) {
            throw new \InvalidArgumentException(__('ecommerce.shipping_rate_not_applicable'));
        }

        $freeShipping = $this->qualifiesForFreeShipping($subtotal, $rate->shipping_zone_id);

        if ($freeShipping) {
            return [
                'name' => $rate->name,
                'amount' => 0.0,
                'free_shipping' => true,
                'estimated_days' => $rate->estimated_days,
            ];
        }

        return [
            'name' => $rate->name,
            'amount' => $this->resolveRateAmount($rate),
            'free_shipping' => false,
            'estimated_days' => $rate->estimated_days,
        ];
    }

    public function getAvailableRates(
        ?string $country = 'EG',
        float $subtotal = 0,
        float $weight = 0,
    ): Collection {
        return ShippingRate::query()
            ->with('zone')
            ->where('is_active', true)
            ->whereHas('zone', function ($query) use ($country) {
                $query->where('is_active', true)
                    ->where(function ($zoneQuery) use ($country) {
                        $zoneQuery->whereNull('countries')
                            ->orWhere('countries', '[]')
                            ->orWhereJsonContains('countries', strtoupper($country ?? 'EG'));
                    });
            })
            ->orderBy('rate')
            ->get()
            ->filter(fn (ShippingRate $rate) => $this->rateApplies($rate, $subtotal, $weight))
            ->values();
    }

    public function qualifiesForFreeShipping(float $subtotal, ?int $zoneId = null): bool
    {
        return FreeShippingRule::query()
            ->where('is_active', true)
            ->where('min_order_amount', '<=', $subtotal)
            ->where(function ($query) use ($zoneId) {
                $query->whereNull('shipping_zone_id');
                if ($zoneId) {
                    $query->orWhere('shipping_zone_id', $zoneId);
                }
            })
            ->exists();
    }

    public function zoneCoversCountry(ShippingZone $zone, string $country): bool
    {
        $countries = $zone->countries;
        if (empty($countries)) {
            return true;
        }

        return in_array(strtoupper($country), array_map('strtoupper', $countries), true);
    }

    public function rateApplies(ShippingRate $rate, float $subtotal, float $weight): bool
    {
        return match (ShippingRateType::tryFrom($rate->type) ?? ShippingRateType::Flat) {
            ShippingRateType::Flat => true,
            ShippingRateType::Weight => $this->valueInRange($weight, $rate->min_value, $rate->max_value),
            ShippingRateType::Price => $this->valueInRange($subtotal, $rate->min_value, $rate->max_value),
        };
    }

    protected function resolveRateAmount(ShippingRate $rate): float
    {
        return (float) $rate->rate;
    }

    protected function valueInRange(float $value, ?float $min, ?float $max): bool
    {
        if ($min !== null && $value < (float) $min) {
            return false;
        }
        if ($max !== null && $value > (float) $max) {
            return false;
        }

        return true;
    }
}
