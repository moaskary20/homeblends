<?php

namespace App\Filament\Resources;

use App\Enums\InstallmentContractStatus;
use App\Enums\InstallmentPaymentStatus;
use App\Filament\Resources\InstallmentContractResource\Pages;
use App\Filament\Resources\InstallmentContractResource\RelationManagers\OrderItemsRelationManager;
use App\Models\InstallmentContract;
use App\Services\Installment\InstallmentPaymentService;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InstallmentContractResource extends Resource
{
    protected static ?string $model = InstallmentContract::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.sales');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.installment_contracts');
    }

    public static function getModelLabel(): string
    {
        return __('ecommerce.installment_contract');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && ($user->is_admin || $user->can('offers.manage') || $user->can('orders.view'));
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make(__('ecommerce.installment_contract'))
                ->schema([
                    Infolists\Components\TextEntry::make('order.order_number')->label(__('ecommerce.order_number')),
                    Infolists\Components\TextEntry::make('user.name')->label(__('ecommerce.customer')),
                    Infolists\Components\TextEntry::make('offer.name')->label(__('ecommerce.installment_offer')),
                    Infolists\Components\TextEntry::make('months')->label(__('ecommerce.installment_months')),
                    Infolists\Components\TextEntry::make('total_amount')->label(__('ecommerce.total'))->money('EGP', locale: 'ar'),
                    Infolists\Components\TextEntry::make('status')
                        ->label(__('ecommerce.status'))
                        ->badge()
                        ->formatStateUsing(fn (InstallmentContractStatus $state) => $state->label()),
                ])
                ->columns(3),
            Infolists\Components\RepeatableEntry::make('installments')
                ->label(__('ecommerce.installment_schedule'))
                ->schema([
                    Infolists\Components\TextEntry::make('sequence')->label('#'),
                    Infolists\Components\TextEntry::make('due_date')->date('d/m/Y')->label(__('ecommerce.installment_due_date')),
                    Infolists\Components\TextEntry::make('amount')->money('EGP', locale: 'ar')->label(__('ecommerce.amount')),
                    Infolists\Components\TextEntry::make('status')
                        ->badge()
                        ->formatStateUsing(fn (InstallmentPaymentStatus $state) => $state->label()),
                    Infolists\Components\TextEntry::make('paid_at')->dateTime('d/m/Y H:i')->placeholder('—'),
                ])
                ->columns(5),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label(__('ecommerce.order_number'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('ecommerce.customer'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('offer.name')
                    ->label(__('ecommerce.installment_offer')),
                Tables\Columns\TextColumn::make('months')
                    ->label(__('ecommerce.installment_months')),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('EGP', locale: 'ar'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (InstallmentContractStatus $state) => $state->label())
                    ->color(fn (InstallmentContractStatus $state) => match ($state) {
                        InstallmentContractStatus::Completed => 'success',
                        InstallmentContractStatus::Overdue => 'danger',
                        InstallmentContractStatus::Cancelled => 'gray',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d/m/Y')->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('mark_next_paid')
                    ->label(__('ecommerce.installment_mark_paid'))
                    ->visible(fn (InstallmentContract $record) => $record->isOpen() && $record->nextUnpaid())
                    ->requiresConfirmation()
                    ->action(function (InstallmentContract $record): void {
                        $record->load('installments');
                        $next = $record->nextUnpaid();
                        if ($next) {
                            app(InstallmentPaymentService::class)->markPaid($next);
                        }
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            OrderItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstallmentContracts::route('/'),
            'view' => Pages\ViewInstallmentContract::route('/{record}'),
        ];
    }
}
