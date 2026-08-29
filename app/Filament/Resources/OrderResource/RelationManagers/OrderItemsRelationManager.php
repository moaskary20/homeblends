<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Enums\OrderStatus;
use App\Models\OrderItem;
use App\Services\Order\AdminOrderService;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class OrderItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('ecommerce.order_items');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_name')
            ->columns([
                Tables\Columns\TextColumn::make('product_name')->label(__('ecommerce.product')),
                Tables\Columns\TextColumn::make('sku')->label(__('ecommerce.sku')),
                Tables\Columns\TextColumn::make('quantity')->label(__('ecommerce.quantity')),
                Tables\Columns\TextColumn::make('unit_price')->money('EGP', locale: 'ar')->label(__('ecommerce.regular_price')),
                Tables\Columns\TextColumn::make('total')->money('EGP', locale: 'ar')->label(__('ecommerce.total')),
            ])
            ->actions([
                Tables\Actions\Action::make('remove')
                    ->label(__('ecommerce.remove_order_item'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('ecommerce.remove_order_item'))
                    ->modalDescription(__('ecommerce.remove_order_item_confirm'))
                    ->modalSubmitActionLabel(__('ecommerce.remove_order_item'))
                    ->hidden(fn (): bool => $this->isReadOnly() || in_array(
                        $this->getOwnerRecord()->status,
                        [OrderStatus::Cancelled, OrderStatus::Refunded],
                        true
                    ))
                    ->disabled(fn (): bool => $this->getOwnerRecord()->items()->count() <= 1)
                    ->tooltip(fn (): ?string => $this->getOwnerRecord()->items()->count() <= 1
                        ? __('ecommerce.cannot_remove_last_order_item')
                        : null)
                    ->action(function (OrderItem $record): void {
                        try {
                            app(AdminOrderService::class)->removeItem(
                                $this->getOwnerRecord(),
                                $record,
                                auth()->user()
                            );
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title(collect($e->errors())->flatten()->first())
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title(__('ecommerce.order_item_removed'))
                            ->success()
                            ->send();

                        $this->dispatch('refreshOrderForm');
                    }),
            ]);
    }
}
