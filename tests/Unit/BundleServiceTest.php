<?php

namespace Tests\Unit;

use App\Models\BundleItem;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductBundle;
use App\Services\Bundle\BundleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BundleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculates_savings_from_component_prices(): void
    {
        $product = Product::factory()->create(['regular_price' => 500, 'stock_quantity' => 10]);
        $product2 = Product::factory()->create(['regular_price' => 300, 'stock_quantity' => 10]);

        $bundle = ProductBundle::create([
            'name' => 'Kitchen Pack',
            'slug' => 'kitchen-pack',
            'bundle_price' => 600,
            'is_active' => true,
        ]);

        BundleItem::create([
            'product_bundle_id' => $bundle->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'sort_order' => 0,
        ]);
        BundleItem::create([
            'product_bundle_id' => $bundle->id,
            'product_id' => $product2->id,
            'quantity' => 2,
            'sort_order' => 1,
        ]);

        $service = app(BundleService::class);

        $this->assertSame(1100.0, $service->calculateRegularTotal($bundle));
        $this->assertSame(500.0, $service->calculateSavings($bundle));
        $this->assertSame(45.5, $service->savingsPercent($bundle));
    }

    public function test_add_to_cart_stores_bundle_line_with_snapshot(): void
    {
        $product = Product::factory()->create(['regular_price' => 400, 'stock_quantity' => 5]);
        $bundle = ProductBundle::create([
            'name' => 'Starter Bundle',
            'slug' => 'starter-bundle',
            'bundle_price' => 350,
            'is_active' => true,
        ]);
        BundleItem::create([
            'product_bundle_id' => $bundle->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'sort_order' => 0,
        ]);

        $cart = Cart::create(['session_id' => 'test-session']);
        $item = app(BundleService::class)->addToCart($cart, $bundle, 2);

        $this->assertSame($bundle->id, $item->product_bundle_id);
        $this->assertSame(2, $item->quantity);
        $this->assertSame(350.0, (float) $item->unit_price);
        $this->assertIsArray($item->bundle_snapshot);
        $this->assertSame('Starter Bundle', $item->bundle_snapshot['name']);
        $this->assertCount(1, $item->bundle_snapshot['items']);
    }

    public function test_bundle_availability_respects_schedule(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 1]);
        $bundle = ProductBundle::create([
            'name' => 'Future',
            'slug' => 'future-bundle',
            'bundle_price' => 100,
            'is_active' => true,
            'starts_at' => now()->addDay(),
        ]);
        BundleItem::create([
            'product_bundle_id' => $bundle->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'sort_order' => 0,
        ]);

        $this->assertFalse($bundle->isAvailable());
    }
}
