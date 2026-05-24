<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Analytics\Concerns\InteractsWithAnalyticsPeriod;
use App\Services\Analytics\AnalyticsService;
use Filament\Widgets\ChartWidget;

class OrdersChartWidget extends ChartWidget
{
    use InteractsWithAnalyticsPeriod;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 1,
    ];

    public function getHeading(): string
    {
        return __('ecommerce.sales_reports');
    }

    protected function getData(): array
    {
        $chart = app(AnalyticsService::class)->getOrdersChart($this->getAnalyticsRange());

        return [
            'datasets' => [
                [
                    'label' => __('ecommerce.orders'),
                    'data' => $chart['data'],
                    'backgroundColor' => '#f59e0b',
                ],
            ],
            'labels' => $chart['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
