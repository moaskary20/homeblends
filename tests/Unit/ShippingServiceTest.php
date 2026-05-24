<?php

namespace Tests\Unit;

use App\Models\FreeShippingRule;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\Shipping\ShippingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ShippingService $service;

    protected ShippingZone $zone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ShippingService::class);
        $this->zone = ShippingZone::create([
            'name' => 'مصر',
            'countries' => ['EG'],
            'is_active' => true,
        ]);
    }

    public function test_flat_rate_is_calculated(): void
    {
        $rate = ShippingRate::create([
            'shipping_zone_id' => $this->zone->id,
            'name' => 'عادي',
            'type' => 'flat',
            'rate' => 50,
            'is_active' => true,
        ]);

        $result = $this->service->calculate($rate->id, 200, 0, 'EG');

        $this->assertSame(50.0, $result['amount']);
        $this->assertFalse($result['free_shipping']);
    }

    public function test_free_shipping_when_min_order_met(): void
    {
        $rate = ShippingRate::create([
            'shipping_zone_id' => $this->zone->id,
            'name' => 'عادي',
            'type' => 'flat',
            'rate' => 50,
            'is_active' => true,
        ]);

        FreeShippingRule::create([
            'shipping_zone_id' => $this->zone->id,
            'min_order_amount' => 500,
            'is_active' => true,
        ]);

        $result = $this->service->calculate($rate->id, 600, 0, 'EG');

        $this->assertSame(0.0, $result['amount']);
        $this->assertTrue($result['free_shipping']);
    }

    public function test_price_tier_rate_applies_to_subtotal(): void
    {
        $rate = ShippingRate::create([
            'shipping_zone_id' => $this->zone->id,
            'name' => 'طلب كبير',
            'type' => 'price',
            'min_value' => 100,
            'max_value' => 1000,
            'rate' => 30,
            'is_active' => true,
        ]);

        $result = $this->service->calculate($rate->id, 250, 0, 'EG');
        $this->assertSame(30.0, $result['amount']);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->calculate($rate->id, 50, 0, 'EG');
    }

    public function test_get_available_rates_filters_by_country(): void
    {
        ShippingRate::create([
            'shipping_zone_id' => $this->zone->id,
            'name' => 'عادي',
            'type' => 'flat',
            'rate' => 50,
            'is_active' => true,
        ]);

        $rates = $this->service->getAvailableRates('EG');
        $this->assertCount(1, $rates);

        $ratesSa = $this->service->getAvailableRates('SA');
        $this->assertCount(0, $ratesSa);
    }
}
