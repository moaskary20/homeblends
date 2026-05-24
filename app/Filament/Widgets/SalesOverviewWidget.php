<?php

namespace App\Filament\Widgets;

use App\Repositories\Contracts\OrderRepositoryInterface;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $stats = app(OrderRepositoryInterface::class)->getSalesStats('month');

        return [
            Stat::make(__('Revenue (Month)'), number_format($stats['total_revenue'], 2).' EGP'),
            Stat::make(__('Orders'), $stats['orders_count']),
            Stat::make(__('Avg. Order'), number_format($stats['avg_order_value'], 2).' EGP'),
        ];
    }
}
