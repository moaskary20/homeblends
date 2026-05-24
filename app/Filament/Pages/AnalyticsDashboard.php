<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Analytics\AnalyticsOverviewWidget;
use App\Filament\Widgets\Analytics\BestSellingProductsWidget;
use App\Filament\Widgets\Analytics\CustomerInsightsWidget;
use App\Filament\Widgets\Analytics\OrdersChartWidget;
use App\Filament\Widgets\Analytics\RevenueChartWidget;
use App\Filament\Widgets\Analytics\TopCustomersWidget;
use Filament\Pages\Dashboard;

class AnalyticsDashboard extends Dashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string $routePath = 'analytics';

    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.pages.analytics-dashboard';

    public ?string $period = '30';

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.analytics');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.analytics_dashboard');
    }

    protected static ?string $title = null;

    public function getTitle(): string
    {
        return __('ecommerce.analytics_dashboard');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->is_admin || $user->can('analytics.view') || $user->can('orders.view'));
    }

    public function getWidgets(): array
    {
        return [
            AnalyticsOverviewWidget::class,
            RevenueChartWidget::class,
            OrdersChartWidget::class,
            CustomerInsightsWidget::class,
            BestSellingProductsWidget::class,
            TopCustomersWidget::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 2;
    }

    public function getPeriod(): string
    {
        return $this->period ?? '30';
    }

    /**
     * @return array<string, mixed>
     */
    public function getWidgetData(): array
    {
        return [
            'period' => $this->getPeriod(),
        ];
    }
}
