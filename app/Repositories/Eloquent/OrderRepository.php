<?php

namespace App\Repositories\Eloquent;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderRepository extends BaseRepository implements OrderRepositoryInterface
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    public function forUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with(['items.product'])
            ->where('user_id', $userId)
            ->latest()
            ->paginate($perPage);
    }

    public function getSalesStats(string $period = 'month'): array
    {
        $start = match ($period) {
            'week' => now()->startOfWeek(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $orders = $this->model->newQuery()
            ->where('created_at', '>=', $start)
            ->whereIn('status', [OrderStatus::Delivered, OrderStatus::Shipped, OrderStatus::Confirmed]);

        return [
            'total_revenue' => (float) (clone $orders)->sum('total'),
            'orders_count' => (clone $orders)->count(),
            'avg_order_value' => (float) (clone $orders)->avg('total'),
            'top_products' => DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.created_at', '>=', $start)
                ->select('order_items.product_id', DB::raw('SUM(order_items.quantity) as total_qty'))
                ->groupBy('order_items.product_id')
                ->orderByDesc('total_qty')
                ->limit(10)
                ->get(),
        ];
    }
}
