<?php

namespace App\Filament\Resources;

use App\Enums\AffiliatePayoutStatus;
use App\Filament\Resources\AffiliatePayoutResource\Pages;
use App\Models\AffiliatePayout;
use App\Services\Affiliate\AffiliatePayoutService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AffiliatePayoutResource extends Resource
{
    protected static ?string $model = AffiliatePayout::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.affiliates');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.affiliate_payouts');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && ($user->is_admin || $user->can('affiliates.manage'));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('affiliate.display_name')->label(__('ecommerce.affiliate')),
                Tables\Columns\TextColumn::make('amount')->money('EGP', locale: 'ar'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('payment_method')->label(__('ecommerce.payment_method')),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d/m/Y'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('mark_paid')
                    ->label(__('ecommerce.mark_paid'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (AffiliatePayout $record) => in_array($record->status, [AffiliatePayoutStatus::Pending, AffiliatePayoutStatus::Processing], true))
                    ->form([
                        Forms\Components\TextInput::make('payment_reference')
                            ->label(__('ecommerce.payment_reference')),
                        Forms\Components\Textarea::make('admin_notes')->label(__('ecommerce.admin_notes')),
                    ])
                    ->action(function (AffiliatePayout $record, array $data): void {
                        app(AffiliatePayoutService::class)->markPaid(
                            $record,
                            auth()->user(),
                            $data['payment_reference'] ?? null,
                            $data['admin_notes'] ?? null
                        );
                        Notification::make()->title(__('ecommerce.payout_marked_paid'))->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAffiliatePayouts::route('/'),
        ];
    }
}
