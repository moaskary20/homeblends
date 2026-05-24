<?php

namespace App\Filament\Affiliate\Widgets;

use App\Enums\AffiliateCommissionStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AffiliateStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $affiliate = auth()->user()?->affiliate;

        if (! $affiliate) {
            return [];
        }

        $pendingCommissions = $affiliate->commissions()
            ->where('status', AffiliateCommissionStatus::Pending)
            ->sum('commission_amount');

        return [
            Stat::make(__('ecommerce.balance'), number_format((float) $affiliate->balance, 2).' '.__('EGP')),
            Stat::make(__('ecommerce.total_earned'), number_format((float) $affiliate->total_earned, 2).' '.__('EGP')),
            Stat::make(__('ecommerce.total_clicks'), (string) $affiliate->total_clicks),
            Stat::make(__('ecommerce.total_orders'), (string) $affiliate->total_orders),
            Stat::make(__('ecommerce.commission_pending'), number_format((float) $pendingCommissions, 2).' '.__('EGP')),
        ];
    }
}
