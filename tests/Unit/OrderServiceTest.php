<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\Order\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_status_logs_history(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'HB-TEST001',
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
            'billing_address' => ['country' => 'EG'],
            'shipping_address' => ['country' => 'EG'],
            'subtotal' => 100,
            'total' => 100,
            'currency' => 'EGP',
            'payment_status' => 'pending',
        ]);

        $service = app(OrderService::class);
        $service->updateStatus($order, OrderStatus::Confirmed, 'تم التأكيد', $user);

        $order->refresh();
        $this->assertSame(OrderStatus::Confirmed, $order->status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_set_tracking_number_marks_shipped(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'HB-TEST002',
            'user_id' => $user->id,
            'status' => OrderStatus::Processing,
            'billing_address' => ['country' => 'EG'],
            'shipping_address' => ['country' => 'EG'],
            'subtotal' => 200,
            'total' => 200,
            'currency' => 'EGP',
            'payment_status' => 'paid',
        ]);

        $service = app(OrderService::class);
        $service->setTrackingNumber($order, 'TRACK123', null, $user);

        $order->refresh();
        $this->assertSame('TRACK123', $order->tracking_number);
        $this->assertSame(OrderStatus::Shipped, $order->status);
    }
}
