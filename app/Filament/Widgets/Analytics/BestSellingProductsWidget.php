<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Analytics\Concerns\InteractsWithAnalyticsPeriod;
use App\Services\Analytics\AnalyticsService;
use Filament\Widgets\Widget;

class BestSellingProductsWidget extends Widget
{
    use InteractsWithAnalyticsPeriod;

    protected static string $view = 'filament.widgets.analytics.best-selling-products';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 1,
    ];

    public function getProducts()
    {
        return app(AnalyticsService::class)->getBestSellingProducts($this->getAnalyticsRange());
    }
}
