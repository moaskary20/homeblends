<?php

namespace App\Filament\Widgets\Analytics\Concerns;

use App\Filament\Pages\AnalyticsDashboard;
use App\Services\Analytics\AnalyticsService;
use Livewire\Attributes\Reactive;
use Livewire\Livewire;

trait InteractsWithAnalyticsPeriod
{
    #[Reactive]
    public ?string $period = '30';

    public static function canView(): bool
    {
        return Livewire::current() instanceof AnalyticsDashboard;
    }

    protected function getAnalyticsPeriod(): string
    {
        return $this->period ?? '30';
    }

    protected function getAnalyticsRange(): array
    {
        return app(AnalyticsService::class)->resolveRange($this->getAnalyticsPeriod());
    }
}
