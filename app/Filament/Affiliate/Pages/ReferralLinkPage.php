<?php

namespace App\Filament\Affiliate\Pages;

use Filament\Pages\Page;

class ReferralLinkPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static string $view = 'filament.affiliate.pages.referral-link';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.referral_link');
    }

    public function getReferralUrl(): string
    {
        return auth()->user()->affiliate?->referralUrl() ?? '';
    }

    public function getReferralCode(): string
    {
        return auth()->user()->affiliate?->code ?? '';
    }
}
