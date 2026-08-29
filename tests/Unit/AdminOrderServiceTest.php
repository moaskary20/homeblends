<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\TaxRate;
use App\Models\User;
use App\Services\Order\AdminOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
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

    public function test_removing_item_recalculates_invoice_and_restores_stock(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create();
        $kept = Product::factory()->create([
            'stock_quantity' => 8,
            'regular_price' => 200,
            'discount_price' => null,
            'weight' => 1,
        ]);
        $removed = Product::factory()->create([
            'stock_quantity' => 5,
            'regular_price' => 300,
            'discount_price' => null,
            'weight' => 1,
        ]);

        TaxRate::create([
            'country' => 'EG',
            'name' => 'VAT',
            'rate' => 14,
            'is_active' => true,
        ]);

        $order = app(AdminOrderService::class)->create([
            'customer_type' => 'registered',
            'user_id' => $customer->id,
            'items' => [
                ['product_id' => $kept->id, 'quantity' => 1, 'unit_price' => 200],
                ['product_id' => $removed->id, 'quantity' => 1, 'unit_price' => 300],
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

        $this->assertSame(500.0, (float) $order->subtotal);
        $this->assertSame(70.0, (float) $order->tax_amount);
        $this->assertSame(570.0, (float) $order->total);

        $itemToRemove = $order->items->firstWhere('product_id', $removed->id);
        $updated = app(AdminOrderService::class)->removeItem($order, $itemToRemove, $admin);

        $this->assertCount(1, $updated->items);
        $this->assertSame($kept->id, $updated->items->first()->product_id);
        $this->assertSame(200.0, (float) $updated->subtotal);
        $this->assertSame(28.0, (float) $updated->tax_amount);
        $this->assertSame(228.0, (float) $updated->total);
        $this->assertSame(5, $removed->fresh()->stock_quantity);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'user_id' => $admin->id,
        ]);
        $this->assertDatabaseMissing('order_items', [
            'id' => $itemToRemove->id,
        ]);
    }

    public function test_cannot_remove_last_order_item(): void
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
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 250],
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
            'payment_status' => 'pending',
            'status' => OrderStatus::Pending->value,
            'decrement_stock' => true,
            'send_notification' => false,
        ], $admin);

        $this->expectException(ValidationException::class);

        app(AdminOrderService::class)->removeItem($order, $order->items->first(), $admin);
    }

    public function test_removing_item_drops_coupon_when_minimum_is_no_longer_met(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create();
        $kept = Product::factory()->create([
            'stock_quantity' => 10,
            'regular_price' => 100,
            'discount_price' => null,
        ]);
        $removed = Product::factory()->create([
            'stock_quantity' => 10,
            'regular_price' => 400,
            'discount_price' => null,
        ]);

        $coupon = Coupon::create([
            'code' => 'SAVE10',
            'type' => 'percentage',
            'value' => 10,
            'min_cart_amount' => 400,
            'is_active' => true,
        ]);

        $order = app(AdminOrderService::class)->create([
            'customer_type' => 'registered',
            'user_id' => $customer->id,
            'items' => [
                ['product_id' => $kept->id, 'quantity' => 1, 'unit_price' => 100],
                ['product_id' => $removed->id, 'quantity' => 1, 'unit_price' => 400],
            ],
            'coupon_code' => 'SAVE10',
            'shipping_name' => $customer->name,
            'shipping_phone' => '01000000000',
            'shipping_email' => $customer->email,
            'shipping_city' => 'القاهرة',
            'shipping_address_line' => 'شارع 1',
            'shipping_country' => 'EG',
            'billing_same_as_shipping' => true,
            'manual_free_shipping' => true,
            'payment_method' => 'cash',
            'payment_status' => 'pending',
            'status' => OrderStatus::Pending->value,
            'decrement_stock' => false,
            'send_notification' => false,
        ], $admin);

        $this->assertSame($coupon->id, $order->coupon_id);
        $this->assertSame(50.0, (float) $order->discount_amount);

        $itemToRemove = $order->items->firstWhere('product_id', $removed->id);
        $updated = app(AdminOrderService::class)->removeItem($order, $itemToRemove, $admin);

        $this->assertNull($updated->coupon_id);
        $this->assertSame(100.0, (float) $updated->subtotal);
        $this->assertSame(0.0, (float) $updated->discount_amount);
        $this->assertSame(100.0, (float) $updated->total);
        $this->assertSame(0, $coupon->fresh()->used_count);
    }
}
