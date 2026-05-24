<?php

namespace Tests\Unit;

use App\Models\FlashSale;
use App\Models\FlashSaleProduct;
use App\Models\Product;
use App\Services\FlashSale\FlashSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlashSaleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_applies_flash_price_when_sale_active(): void
    {
        $product = Product::factory()->create([
            'regular_price' => 1000,
            'discount_price' => null,
        ]);

        $sale = FlashSale::create([
            'name' => 'عرض نهاية الأسبوع',
            'slug' => 'weekend-flash',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
        ]);

        FlashSaleProduct::create([
            'flash_sale_id' => $sale->id,
            'product_id' => $product->id,
            'sale_price' => 699,
            'stock_limit' => 10,
        ]);

        $pricing = app(FlashSaleService::class)->resolveUnitPrice($product);

        $this->assertTrue($pricing['is_flash_sale']);
        $this->assertSame(699.0, $pricing['price']);
    }

    public function test_records_sale_and_limits_quantity(): void
    {
        $product = Product::factory()->create(['regular_price' => 500]);

        $sale = FlashSale::create([
            'name' => 'عرض محدود',
            'slug' => 'limited-flash',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
        ]);

        $entry = FlashSaleProduct::create([
            'flash_sale_id' => $sale->id,
            'product_id' => $product->id,
            'sale_price' => 399,
            'stock_limit' => 2,
            'quantity_sold' => 1,
        ]);

        $service = app(FlashSaleService::class);

        $this->assertTrue($entry->fresh()->hasStock(1));
        $this->assertFalse($entry->fresh()->hasStock(2));

        $service->recordSale($entry, 1);

        $this->assertSame(2, $entry->fresh()->quantity_sold);
        $this->assertFalse($entry->fresh()->hasStock(1));
    }
}
