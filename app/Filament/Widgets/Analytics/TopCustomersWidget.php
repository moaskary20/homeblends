<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Analytics\Concerns\InteractsWithAnalyticsPeriod;
use App\Services\Analytics\AnalyticsService;
use Filament\Widgets\Widget;

class TopCustomersWidget extends Widget
{
    use InteractsWithAnalyticsPeriod;

    protected static string $view = 'filament.widgets.analytics.top-customers';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 1,
    ];

    public function getCustomers()
    {
        return app(AnalyticsService::class)->getTopCustomers($this->getAnalyticsRange());
    }
}
