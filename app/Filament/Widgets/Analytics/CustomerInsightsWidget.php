<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Analytics\Concerns\InteractsWithAnalyticsPeriod;
use App\Services\Analytics\AnalyticsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomerInsightsWidget extends BaseWidget
{
    use InteractsWithAnalyticsPeriod;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 1,
    ];

    public function getHeading(): ?string
    {
        return __('ecommerce.customer_analytics');
    }

    protected function getStats(): array
    {
        $insights = app(AnalyticsService::class)->getCustomerAnalytics($this->getAnalyticsRange());

        return [
            Stat::make(__('ecommerce.total_customers'), (string) $insights['total_customers']),
            Stat::make(__('ecommerce.active_customers'), (string) $insights['active_customers'])
                ->description(__('ecommerce.ordered_in_period')),
            Stat::make(__('ecommerce.new_buyers'), (string) $insights['new_buyers']),
            Stat::make(__('ecommerce.repeat_buyers'), (string) $insights['repeat_buyers']),
        ];
    }
}
