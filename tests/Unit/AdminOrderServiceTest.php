<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Order\AdminOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_order_with_items_and_decrements_stock(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create();
        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'regular_price' => 250,
            'discount_price' => null,
        ]);

        $order = app(AdminOrderService::class)->create([
            'customer_type' => 'registered',
            'user_id' => $customer->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 250,
                ],
            ],
            'shipping_name' => $customer->name,
            'shipping_phone' => '01000000000',
            'shipping_email' => $customer->email,
            'shipping_city' => 'القاهرة',
            'shipping_address_line' => 'شارع 1',
            'shipping_country' => 'EG',
            'billing_same_as_shipping' => true,
            'manual_free_shipping' => true,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'status' => OrderStatus::Confirmed->value,
            'decrement_stock' => true,
            'send_notification' => false,
        ], $admin);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertSame(500.0, (float) $order->subtotal);
        $this->assertSame($customer->id, $order->user_id);
        $this->assertCount(1, $order->items);
        $this->assertSame(8, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_guest_order_has_no_user_id(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $product = Product::factory()->create(['stock_quantity' => 5]);

        $order = app(AdminOrderService::class)->create([
            'customer_type' => 'guest',
            'guest_name' => 'ضيف',
            'guest_phone' => '01111111111',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 100,
                ],
            ],
            'shipping_city' => 'الجيزة',
            'shipping_address_line' => 'عنوان',
            'shipping_country' => 'EG',
            'manual_free_shipping' => true,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'pending',
            'status' => OrderStatus::Pending->value,
            'decrement_stock' => false,
            'send_notification' => false,
        ], $admin);

        $this->assertNull($order->user_id);
        $this->assertSame('ضيف', $order->shipping_address['name']);
    }
}
