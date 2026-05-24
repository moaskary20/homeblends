<?php

namespace Database\Seeders;

use App\Models\FreeShippingRule;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxRate;
use Illuminate\Database\Seeder;

class TaxShippingSeeder extends Seeder
{
    public function run(): void
    {
        TaxRate::updateOrCreate(
            ['country' => 'EG'],
            ['name' => 'VAT Egypt', 'rate' => 14, 'is_active' => true]
        );

        $zone = ShippingZone::updateOrCreate(
            ['name' => 'مصر'],
            ['countries' => ['EG'], 'is_active' => true]
        );

        ShippingRate::updateOrCreate(
            ['shipping_zone_id' => $zone->id, 'name' => 'توصيل عادي'],
            ['type' => 'flat', 'rate' => 50, 'estimated_days' => 3, 'is_active' => true]
        );

        ShippingRate::updateOrCreate(
            ['shipping_zone_id' => $zone->id, 'name' => 'توصيل سريع'],
            ['type' => 'flat', 'rate' => 90, 'estimated_days' => 1, 'is_active' => true]
        );

        FreeShippingRule::updateOrCreate(
            ['shipping_zone_id' => $zone->id, 'min_order_amount' => 1000],
            ['is_active' => true]
        );

        FreeShippingRule::updateOrCreate(
            ['shipping_zone_id' => null, 'min_order_amount' => 2000],
            ['is_active' => true]
        );

        $this->command?->info('Tax and shipping defaults seeded (Egypt zone, 2 rates, COD-ready checkout).');
    }
}
