<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Analytics\Concerns\InteractsWithAnalyticsPeriod;
use App\Services\Analytics\AnalyticsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AnalyticsOverviewWidget extends BaseWidget
{
    use InteractsWithAnalyticsPeriod;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $range = $this->getAnalyticsRange();
        $stats = app(AnalyticsService::class)->getSummary($range);
        $change = $stats['revenue_change_percent'];

        return [
            Stat::make(__('ecommerce.total_revenue'), number_format($stats['revenue'], 2).' '.__('EGP'))
                ->description($range['label'])
                ->descriptionIcon($change >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($change >= 0 ? 'success' : 'danger')
                ->chart(array_slice(app(AnalyticsService::class)->getRevenueChart($range)['data'], -7)),
            Stat::make(__('ecommerce.orders_count'), (string) $stats['orders_count'])
                ->description(__('ecommerce.orders_in_period')),
            Stat::make(__('ecommerce.avg_order_value'), number_format($stats['avg_order_value'], 2).' '.__('EGP')),
            Stat::make(__('ecommerce.new_customers'), (string) $stats['new_customers'])
                ->description(__('ecommerce.registered_in_period')),
        ];
    }
}
