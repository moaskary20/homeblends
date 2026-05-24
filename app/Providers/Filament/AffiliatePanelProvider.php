<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SetArabicLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AffiliatePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('affiliate')
            ->path('affiliate')
            ->login()
            ->brandName(__('ecommerce.affiliate_program'))
            ->font('Cairo')
            ->colors(['primary' => Color::Emerald])
            ->discoverResources(in: app_path('Filament/Affiliate/Resources'), for: 'App\\Filament\\Affiliate\\Resources')
            ->discoverPages(in: app_path('Filament/Affiliate/Pages'), for: 'App\\Filament\\Affiliate\\Pages')
            ->pages([
                \App\Filament\Affiliate\Pages\AffiliateDashboard::class,
                \App\Filament\Affiliate\Pages\ReferralLinkPage::class,
            ])
            ->homeUrl(fn (): string => \App\Filament\Affiliate\Pages\AffiliateDashboard::getUrl())
            ->discoverWidgets(in: app_path('Filament/Affiliate/Widgets'), for: 'App\\Filament\\Affiliate\\Widgets')
            ->middleware([
                SetArabicLocale::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class]);
    }
}
