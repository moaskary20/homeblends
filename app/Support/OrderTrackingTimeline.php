<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Support\Collection;

class OrderTrackingTimeline
{
    /** @var list<OrderStatus> */
    private const FLOW = [
        OrderStatus::Pending,
        OrderStatus::Confirmed,
        OrderStatus::Processing,
        OrderStatus::Shipped,
        OrderStatus::Delivered,
    ];

    public function __construct(public Order $order)
    {
        $this->order->loadMissing('statusHistory');
    }

    public function hasHistory(): bool
    {
        return $this->order->statusHistory->isNotEmpty();
    }

    public function isTerminal(): bool
    {
        return in_array($this->order->status, [OrderStatus::Cancelled, OrderStatus::Refunded], true);
    }

    public function showRouteMap(): bool
    {
        return ! $this->isTerminal();
    }

    /**
     * @return Collection<int, array{
     *     status: OrderStatus,
     *     label: string,
     *     state: 'done'|'current'|'upcoming',
     *     icon: string,
     *     reached_at: ?\Illuminate\Support\Carbon,
     *     comment: ?string
     * }>
     */
    public function routeSteps(): Collection
    {
        $activeIndex = $this->activeFlowIndex();
        $isDelivered = $this->order->status === OrderStatus::Delivered;
        $timestamps = $this->firstReachedAtByStatus();

        return collect(self::FLOW)->map(function (OrderStatus $status, int $index) use ($activeIndex, $isDelivered, $timestamps) {
            $state = ($isDelivered || $index < $activeIndex)
                ? 'done'
                : ($index === $activeIndex ? 'current' : 'upcoming');

            $reached = $timestamps->get($status->value);

            return [
                'status' => $status,
                'label' => $status->label(),
                'state' => $state,
                'icon' => $this->iconFor($status),
                'reached_at' => $reached,
                'comment' => $this->commentForStatus($status),
            ];
        });
    }

    public function progressPercent(): int
    {
        if ($this->order->status === OrderStatus::Delivered) {
            return 100;
        }

        if ($this->isTerminal()) {
            return 0;
        }

        $active = $this->activeFlowIndex();
        $total = count(self::FLOW);

        return (int) round((($active + 1) / $total) * 100);
    }

    /**
     * Same records as admin panel status history (newest first).
     *
     * @return Collection<int, array{
     *     status_label: string,
     *     comment: ?string,
     *     created_at: \Illuminate\Support\Carbon
     * }>
     */
    public function historyLog(): Collection
    {
        return $this->order->statusHistory
            ->sortByDesc('created_at')
            ->values()
            ->map(fn (OrderStatusHistory $entry) => [
                'status_label' => OrderStatus::tryFrom($entry->status)?->label() ?? $entry->status,
                'comment' => $entry->comment,
                'created_at' => $entry->created_at,
            ]);
    }

    public function terminalLabel(): ?string
    {
        return match ($this->order->status) {
            OrderStatus::Cancelled => $this->order->status->label(),
            OrderStatus::Refunded => $this->order->status->label(),
            default => null,
        };
    }

    public function terminalIcon(): string
    {
        return match ($this->order->status) {
            OrderStatus::Cancelled => '❌',
            OrderStatus::Refunded => '↩️',
            default => '•',
        };
    }

    private function activeFlowIndex(): int
    {
        $indices = [];

        $current = array_search($this->order->status, self::FLOW, true);
        if ($current !== false) {
            $indices[] = $current;
        }

        foreach ($this->order->statusHistory as $entry) {
            $status = OrderStatus::tryFrom($entry->status);
            if (! $status) {
                continue;
            }
            $index = array_search($status, self::FLOW, true);
            if ($index !== false) {
                $indices[] = $index;
            }
        }

        return $indices !== [] ? max($indices) : 0;
    }

    /**
     * @return Collection<string, \Illuminate\Support\Carbon>
     */
    private function firstReachedAtByStatus(): Collection
    {
        return $this->order->statusHistory
            ->sortBy('created_at')
            ->groupBy('status')
            ->map(fn (Collection $entries) => $entries->first()->created_at);
    }

    private function commentForStatus(OrderStatus $status): ?string
    {
        $entry = $this->order->statusHistory
            ->where('status', $status->value)
            ->sortByDesc('created_at')
            ->first();

        return $entry?->comment;
    }

    private function iconFor(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Pending => '📋',
            OrderStatus::Confirmed => '✅',
            OrderStatus::Processing => '⚙️',
            OrderStatus::Shipped => '🚚',
            OrderStatus::Delivered => '🏠',
            OrderStatus::Cancelled => '❌',
            OrderStatus::Refunded => '↩️',
        };
    }
}
