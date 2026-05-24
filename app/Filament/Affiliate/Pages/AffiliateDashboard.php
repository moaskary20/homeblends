<?php

namespace App\Filament\Affiliate\Pages;

use App\Filament\Affiliate\Widgets\AffiliateStatsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class AffiliateDashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = '';

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.dashboard');
    }

    public function getWidgets(): array
    {
        return [
            AffiliateStatsWidget::class,
        ];
    }
}
