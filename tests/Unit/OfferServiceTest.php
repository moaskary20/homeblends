<?php

namespace Tests\Unit;

use App\Models\Offer;
use App\Models\OfferProduct;
use App\Models\Product;
use App\Services\Offer\OfferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_offer_entry_is_found_for_product(): void
    {
        $product = Product::factory()->create(['regular_price' => 12000]);
        $offer = Offer::create([
            'name' => 'عرض غرفة نوم',
            'slug' => 'bedroom-offer',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addWeek(),
            'is_active' => true,
            'installment_months' => 12,
        ]);
        OfferProduct::create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'offer_price' => 18000,
            'stock_limit' => 5,
        ]);

        $entry = app(OfferService::class)->findActiveEntry($product);

        $this->assertNotNull($entry);
        $this->assertSame(18000.0, (float) $entry->offer_price);
        $this->assertSame(1500.0, $entry->monthlyAmount());
    }

    public function test_offer_normalizes_multiple_installment_plans(): void
    {
        $offer = Offer::create([
            'name' => 'عرض خطط',
            'slug' => 'plans-offer',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addWeek(),
            'is_active' => true,
            'installment_plans' => [12, 6, 12, 40],
        ]);

        $this->assertSame([6, 12], $offer->planMonths());
        $this->assertSame(6, $offer->defaultPlanMonths());
        $this->assertTrue($offer->hasPlan(12));
        $this->assertFalse($offer->hasPlan(18));
        $this->assertSame(1000.0, $offer->monthlyAmountFor(12000, 12));
    }

    public function test_ended_offer_is_not_active(): void
    {
        $product = Product::factory()->create();
        $offer = Offer::create([
            'name' => 'عرض منتهٍ',
            'slug' => 'ended-offer',
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->subDay(),
            'is_active' => true,
            'installment_months' => 6,
        ]);
        OfferProduct::create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'offer_price' => 500,
        ]);

        $this->assertNull(app(OfferService::class)->findActiveEntry($product));
    }
}
