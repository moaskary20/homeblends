<?php

namespace Tests\Unit;

use App\Models\FlashSale;
use App\Models\Product;
use App\Services\FlashSale\FlashSaleProductSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FlashSaleProductSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_syncs_products_to_flash_sale(): void
    {
        $sale = FlashSale::create([
            'name' => 'Weekend Sale',
            'slug' => 'weekend-sale',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
        ]);

        $product = Product::factory()->create(['regular_price' => 1000]);

        app(FlashSaleProductSyncService::class)->sync($sale, [
            [
                'product_id' => $product->id,
                'sale_price' => 750,
                'stock_limit' => 10,
                'sort_order' => 0,
            ],
        ]);

        $this->assertDatabaseHas('flash_sale_products', [
            'flash_sale_id' => $sale->id,
            'product_id' => $product->id,
            'sale_price' => 750,
            'stock_limit' => 10,
        ]);
    }

    public function test_rejects_duplicate_products(): void
    {
        $sale = FlashSale::create([
            'name' => 'Dup Test',
            'slug' => 'dup-test',
            'starts_at' => now(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
        ]);

        $product = Product::factory()->create();

        $this->expectException(ValidationException::class);

        app(FlashSaleProductSyncService::class)->sync($sale, [
            ['product_id' => $product->id, 'sale_price' => 100],
            ['product_id' => $product->id, 'sale_price' => 90],
        ]);
    }
}
