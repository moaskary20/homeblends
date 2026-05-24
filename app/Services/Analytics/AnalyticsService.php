<?php

namespace App\Services\Analytics;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /** @return array{from: Carbon, to: Carbon, label: string} */
    public function resolveRange(string $period): array
    {
        $to = now()->endOfDay();

        return match ($period) {
            '7' => [
                'from' => now()->subDays(6)->startOfDay(),
                'to' => $to,
                'label' => __('ecommerce.period_7_days'),
            ],
            '90' => [
                'from' => now()->subDays(89)->startOfDay(),
                'to' => $to,
                'label' => __('ecommerce.period_90_days'),
            ],
            'year' => [
                'from' => now()->startOfYear(),
                'to' => $to,
                'label' => __('ecommerce.period_year'),
            ],
            'month' => [
                'from' => now()->startOfMonth(),
                'to' => $to,
                'label' => __('ecommerce.period_month'),
            ],
            default => [
                'from' => now()->subDays(29)->startOfDay(),
                'to' => $to,
                'label' => __('ecommerce.period_30_days'),
            ],
        };
    }

    public function baseOrdersQuery(Carbon $from, Carbon $to)
    {
        return Order::query()
            ->whereBetween('created_at', [$from, $to])
            ->whereNotIn('status', [OrderStatus::Cancelled->value]);
    }

    public function getSummary(array $range): array
    {
        $orders = $this->baseOrdersQuery($range['from'], $range['to']);
        $revenue = (float) (clone $orders)->sum('total');
        $count = (clone $orders)->count();
        $avg = $count > 0 ? $revenue / $count : 0;

        $previous = $this->previousRange($range);
        $prevOrders = $this->baseOrdersQuery($previous['from'], $previous['to']);
        $prevRevenue = (float) (clone $prevOrders)->sum('total');
        $revenueChange = $prevRevenue > 0 ? round((($revenue - $prevRevenue) / $prevRevenue) * 100, 1) : 0;

        $newCustomers = User::query()
            ->whereBetween('created_at', [$range['from'], $range['to']])
            ->count();

        return [
            'revenue' => $revenue,
            'orders_count' => $count,
            'avg_order_value' => $avg,
            'new_customers' => $newCustomers,
            'revenue_change_percent' => $revenueChange,
            'refunded_count' => Order::query()
                ->whereBetween('created_at', [$range['from'], $range['to']])
                ->where('status', OrderStatus::Refunded->value)
                ->count(),
        ];
    }

    public function getRevenueChart(array $range): array
    {
        $rows = $this->baseOrdersQuery($range['from'], $range['to'])
            ->selectRaw('DATE(created_at) as day, SUM(total) as revenue')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('revenue', 'day');

        return $this->fillDailySeries($range['from'], $range['to'], $rows);
    }

    public function getOrdersChart(array $range): array
    {
        $rows = $this->baseOrdersQuery($range['from'], $range['to'])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        return $this->fillDailySeries($range['from'], $range['to'], $rows);
    }

    public function getBestSellingProducts(array $range, int $limit = 10): Collection
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->whereBetween('orders.created_at', [$range['from'], $range['to']])
            ->whereNotIn('orders.status', [OrderStatus::Cancelled->value])
            ->select(
                'order_items.product_id',
                'order_items.product_name',
                DB::raw('SUM(order_items.quantity) as units_sold'),
                DB::raw('SUM(order_items.total) as revenue')
            )
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc('units_sold')
            ->limit($limit)
            ->get();
    }

    public function getCustomerAnalytics(array $range): array
    {
        $customerIds = $this->baseOrdersQuery($range['from'], $range['to'])
            ->distinct()
            ->pluck('user_id');

        $newBuyers = (int) DB::table('orders')
            ->whereBetween('created_at', [$range['from'], $range['to']])
            ->whereNotIn('status', [OrderStatus::Cancelled->value])
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) = 1')
            ->get()
            ->count();

        $active = $customerIds->count();
        $repeatBuyers = max(0, $active - $newBuyers);

        return [
            'total_customers' => User::count(),
            'active_customers' => $customerIds->count(),
            'new_customers' => User::query()
                ->whereBetween('created_at', [$range['from'], $range['to']])
                ->count(),
            'new_buyers' => max(0, $newBuyers),
            'repeat_buyers' => max(0, $repeatBuyers),
        ];
    }

    public function getTopCustomers(array $range, int $limit = 10): Collection
    {
        $rows = DB::table('orders')
            ->whereBetween('created_at', [$range['from'], $range['to']])
            ->whereNotIn('status', [OrderStatus::Cancelled->value])
            ->whereNotNull('user_id')
            ->select('user_id', DB::raw('COUNT(*) as orders_count'), DB::raw('SUM(total) as total_spent'))
            ->groupBy('user_id')
            ->orderByDesc('total_spent')
            ->limit($limit)
            ->get();

        $users = User::query()
            ->whereIn('id', $rows->pluck('user_id'))
            ->get()
            ->keyBy('id');

        return $rows->map(fn ($row) => (object) [
            'user' => $users->get($row->user_id),
            'orders_count' => (int) $row->orders_count,
            'total_spent' => (float) $row->total_spent,
        ]);
    }

    public function getSalesByStatus(array $range): array
    {
        return $this->baseOrdersQuery($range['from'], $range['to'])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();
    }

    protected function fillDailySeries(Carbon $from, Carbon $to, $rows): array
    {
        $labels = [];
        $data = [];
        $cursor = $from->copy()->startOfDay();

        while ($cursor <= $to) {
            $key = $cursor->format('Y-m-d');
            $labels[] = $cursor->format('d/m');
            $data[] = (float) ($rows[$key] ?? 0);
            $cursor->addDay();
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /** @return array{from: Carbon, to: Carbon} */
    protected function previousRange(array $range): array
    {
        $days = $range['from']->diffInDays($range['to']) + 1;

        return [
            'from' => $range['from']->copy()->subDays($days)->startOfDay(),
            'to' => $range['from']->copy()->subDay()->endOfDay(),
        ];
    }
}
