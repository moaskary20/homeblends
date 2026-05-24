<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_excludes_cancelled_orders(): void
    {
        $user = User::factory()->create();
        $service = app(AnalyticsService::class);
        $range = $service->resolveRange('30');

        Order::create([
            'order_number' => 'HB-A001',
            'user_id' => $user->id,
            'status' => OrderStatus::Confirmed,
            'billing_address' => ['country' => 'EG'],
            'shipping_address' => ['country' => 'EG'],
            'subtotal' => 500,
            'total' => 500,
            'currency' => 'EGP',
            'payment_status' => 'paid',
            'created_at' => now(),
        ]);

        Order::create([
            'order_number' => 'HB-A002',
            'user_id' => $user->id,
            'status' => OrderStatus::Cancelled,
            'billing_address' => ['country' => 'EG'],
            'shipping_address' => ['country' => 'EG'],
            'subtotal' => 999,
            'total' => 999,
            'currency' => 'EGP',
            'payment_status' => 'pending',
            'created_at' => now(),
        ]);

        $summary = $service->getSummary($range);

        $this->assertSame(500.0, $summary['revenue']);
        $this->assertSame(1, $summary['orders_count']);
    }

    public function test_best_selling_products_aggregates_items(): void
    {
        $user = User::factory()->create();
        $service = app(AnalyticsService::class);
        $range = $service->resolveRange('30');

        $product = Product::factory()->create(['name' => 'قهوة عربية']);

        $order = Order::create([
            'order_number' => 'HB-A003',
            'user_id' => $user->id,
            'status' => OrderStatus::Delivered,
            'billing_address' => ['country' => 'EG'],
            'shipping_address' => ['country' => 'EG'],
            'subtotal' => 300,
            'total' => 300,
            'currency' => 'EGP',
            'payment_status' => 'paid',
            'created_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'قهوة عربية',
            'sku' => $product->sku,
            'quantity' => 2,
            'unit_price' => 100,
            'total' => 200,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'قهوة عربية',
            'sku' => $product->sku,
            'quantity' => 1,
            'unit_price' => 100,
            'total' => 100,
        ]);

        $products = $service->getBestSellingProducts($range);

        $this->assertCount(1, $products);
        $this->assertSame('قهوة عربية', $products->first()->product_name);
        $this->assertSame(3, (int) $products->first()->units_sold);
        $this->assertSame(300.0, (float) $products->first()->revenue);
    }

    public function test_top_customers_ranks_by_spent(): void
    {
        $buyer = User::factory()->create(['name' => 'أحمد']);
        $other = User::factory()->create(['name' => 'سارة']);
        $service = app(AnalyticsService::class);
        $range = $service->resolveRange('30');

        Order::create([
            'order_number' => 'HB-A004',
            'user_id' => $buyer->id,
            'status' => OrderStatus::Confirmed,
            'billing_address' => ['country' => 'EG'],
            'shipping_address' => ['country' => 'EG'],
            'subtotal' => 800,
            'total' => 800,
            'currency' => 'EGP',
            'payment_status' => 'paid',
            'created_at' => now(),
        ]);

        Order::create([
            'order_number' => 'HB-A005',
            'user_id' => $other->id,
            'status' => OrderStatus::Confirmed,
            'billing_address' => ['country' => 'EG'],
            'shipping_address' => ['country' => 'EG'],
            'subtotal' => 150,
            'total' => 150,
            'currency' => 'EGP',
            'payment_status' => 'paid',
            'created_at' => now(),
        ]);

        $top = $service->getTopCustomers($range);

        $this->assertSame('أحمد', $top->first()->user->name);
        $this->assertSame(800.0, $top->first()->total_spent);
    }
}
