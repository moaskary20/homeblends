<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Analytics\Concerns\InteractsWithAnalyticsPeriod;
use App\Services\Analytics\AnalyticsService;
use Filament\Widgets\ChartWidget;

class RevenueChartWidget extends ChartWidget
{
    use InteractsWithAnalyticsPeriod;

    protected static ?string $heading = null;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return __('ecommerce.revenue_analytics');
    }

    protected function getData(): array
    {
        $chart = app(AnalyticsService::class)->getRevenueChart($this->getAnalyticsRange());

        return [
            'datasets' => [
                [
                    'label' => __('ecommerce.revenue'),
                    'data' => $chart['data'],
                    'fill' => true,
                    'borderColor' => '#d97706',
                    'backgroundColor' => 'rgba(217, 119, 6, 0.1)',
                ],
            ],
            'labels' => $chart['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
