<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(protected NotificationDispatcher $notifications) {}

    public function updateStatus(
        Order $order,
        OrderStatus $status,
        ?string $comment = null,
        ?User $actor = null,
    ): Order {
        return DB::transaction(function () use ($order, $status, $comment, $actor) {
            $previous = $order->status;

            if ($previous === $status) {
                return $order;
            }

            $order->update(['status' => $status]);

            if ($status === OrderStatus::Refunded) {
                $order->update(['payment_status' => 'refunded']);
            }

            if ($status === OrderStatus::Cancelled && $order->payment_status === 'pending') {
                $order->update(['payment_status' => 'failed']);
            }

            $historyComment = $comment ?? __('ecommerce.order_status_changed', [
                'from' => $previous->label(),
                'to' => $status->label(),
            ]);

            $order->statusHistory()->create([
                'status' => $status->value,
                'comment' => $historyComment,
                'user_id' => $actor?->id,
            ]);

            $order = $order->fresh(['statusHistory', 'items', 'user']);
            $this->notifications->orderStatusUpdated($order, $historyComment);

            return $order;
        });
    }

    public function setTrackingNumber(
        Order $order,
        string $trackingNumber,
        ?string $comment = null,
        ?User $actor = null,
    ): Order {
        return DB::transaction(function () use ($order, $trackingNumber, $comment, $actor) {
            $order->update(['tracking_number' => $trackingNumber]);

            if (in_array($order->status, [OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Processing], true)) {
                return $this->updateStatus(
                    $order,
                    OrderStatus::Shipped,
                    $comment ?? __('ecommerce.tracking_number_added', ['number' => $trackingNumber]),
                    $actor
                );
            }

            $order->statusHistory()->create([
                'status' => $order->status->value,
                'comment' => $comment ?? __('ecommerce.tracking_number_updated', ['number' => $trackingNumber]),
                'user_id' => $actor?->id,
            ]);

            return $order->fresh(['statusHistory', 'items', 'user']);
        });
    }

    public function canTransition(Order $order, OrderStatus $to): bool
    {
        return match ($order->status) {
            OrderStatus::Pending => in_array($to, [OrderStatus::Confirmed, OrderStatus::Cancelled], true),
            OrderStatus::Confirmed => in_array($to, [OrderStatus::Processing, OrderStatus::Cancelled], true),
            OrderStatus::Processing => in_array($to, [OrderStatus::Shipped, OrderStatus::Cancelled], true),
            OrderStatus::Shipped => in_array($to, [OrderStatus::Delivered, OrderStatus::Refunded], true),
            OrderStatus::Delivered => $to === OrderStatus::Refunded,
            OrderStatus::Cancelled, OrderStatus::Refunded => false,
        };
    }
}
