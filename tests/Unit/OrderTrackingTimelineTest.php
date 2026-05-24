<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Support\OrderTrackingTimeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTrackingTimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_steps_mark_current_and_done_from_admin_history(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'HB-TRACK01',
            'user_id' => $user->id,
            'status' => OrderStatus::Processing,
            'billing_address' => ['country' => 'EG'],
            'shipping_address' => ['country' => 'EG'],
            'subtotal' => 500,
            'total' => 500,
            'currency' => 'EGP',
            'payment_status' => 'pending',
        ]);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => OrderStatus::Pending->value,
            'comment' => 'تم إنشاء الطلب',
        ]);
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => OrderStatus::Confirmed->value,
            'comment' => __('ecommerce.status_updated_from_admin'),
        ]);

        $timeline = new OrderTrackingTimeline($order->fresh('statusHistory'));
        $steps = $timeline->routeSteps();

        $this->assertTrue($timeline->showRouteMap());
        $this->assertSame('done', $steps[0]['state']);
        $this->assertSame('done', $steps[1]['state']);
        $this->assertSame('current', $steps[2]['state']);
        $this->assertSame('upcoming', $steps[3]['state']);
        $this->assertCount(2, $timeline->historyLog());
    }

    public function test_terminal_order_shows_no_route_map(): void
    {
        $order = Order::create([
            'order_number' => 'HB-TRACK02',
            'user_id' => User::factory()->create()->id,
            'status' => OrderStatus::Cancelled,
            'billing_address' => ['country' => 'EG'],
            'shipping_address' => ['country' => 'EG'],
            'subtotal' => 100,
            'total' => 100,
            'currency' => 'EGP',
            'payment_status' => 'pending',
        ]);
        $timeline = new OrderTrackingTimeline($order);

        $this->assertFalse($timeline->showRouteMap());
        $this->assertTrue($timeline->isTerminal());
        $this->assertSame('ملغي', $timeline->terminalLabel());
    }

    public function test_delivered_order_has_full_progress(): void
    {
        $order = Order::create([
            'order_number' => 'HB-TRACK03',
            'user_id' => User::factory()->create()->id,
            'status' => OrderStatus::Delivered,
            'billing_address' => ['country' => 'EG'],
            'shipping_address' => ['country' => 'EG'],
            'subtotal' => 100,
            'total' => 100,
            'currency' => 'EGP',
            'payment_status' => 'paid',
        ]);
        $timeline = new OrderTrackingTimeline($order);

        $this->assertSame(100, $timeline->progressPercent());
        $this->assertTrue($timeline->routeSteps()->every(fn ($s) => $s['state'] === 'done'));
    }
}
