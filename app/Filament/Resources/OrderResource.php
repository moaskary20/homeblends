<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers\OrderItemsRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\StatusHistoriesRelationManager;
use App\Models\Order;
use App\Http\Controllers\Admin\OrderInvoiceController;
use App\Services\Order\OrderService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.sales');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.orders');
    }

    public static function getModelLabel(): string
    {
        return __('ecommerce.order');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && ($user->is_admin || $user->can('orders.manage'));
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('ecommerce.order_details'))
                ->schema([
                    Forms\Components\TextInput::make('order_number')
                        ->label(__('ecommerce.order_number'))
                        ->disabled(),
                    Forms\Components\Select::make('user_id')
                        ->label(__('ecommerce.customer'))
                        ->relationship('user', 'name')
                        ->disabled(),
                    Forms\Components\Select::make('status')
                        ->label(__('ecommerce.status'))
                        ->options(collect(OrderStatus::cases())->mapWithKeys(
                            fn (OrderStatus $s) => [$s->value => $s->label()]
                        ))
                        ->required(),
                    Forms\Components\TextInput::make('tracking_number')
                        ->label(__('ecommerce.tracking_number'))
                        ->maxLength(255),
                    Forms\Components\Select::make('payment_status')
                        ->label(__('ecommerce.payment_status'))
                        ->options([
                            'pending' => __('ecommerce.payment_pending'),
                            'paid' => __('ecommerce.payment_paid'),
                            'failed' => __('ecommerce.payment_failed'),
                            'refunded' => __('ecommerce.payment_refunded'),
                        ]),
                ])
                ->columns(2),
            Forms\Components\Section::make(__('ecommerce.order_amounts'))
                ->schema([
                    Forms\Components\TextInput::make('subtotal')->disabled()->prefix('ج.م'),
                    Forms\Components\TextInput::make('discount_amount')->disabled()->prefix('ج.م'),
                    Forms\Components\TextInput::make('shipping_amount')->disabled()->prefix('ج.م'),
                    Forms\Components\TextInput::make('tax_amount')->disabled()->prefix('ج.م'),
                    Forms\Components\TextInput::make('total')->disabled()->prefix('ج.م'),
                ])
                ->columns(3),
            Forms\Components\Section::make(__('ecommerce.shipping'))
                ->schema([
                    Forms\Components\TextInput::make('shipping_method')
                        ->label(__('ecommerce.shipping_method'))
                        ->disabled(),
                    Forms\Components\KeyValue::make('shipping_address')
                        ->label(__('ecommerce.shipping_address'))
                        ->disabled(),
                ]),
            Forms\Components\Textarea::make('notes')
                ->label(__('ecommerce.notes'))
                ->columnSpanFull(),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make(__('ecommerce.order_details'))
                ->schema([
                    Infolists\Components\TextEntry::make('order_number')->label(__('ecommerce.order_number')),
                    Infolists\Components\TextEntry::make('user.name')->label(__('ecommerce.customer')),
                    Infolists\Components\TextEntry::make('status')
                        ->label(__('ecommerce.status'))
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state instanceof OrderStatus ? $state->label() : $state),
                    Infolists\Components\TextEntry::make('tracking_number')
                        ->label(__('ecommerce.tracking_number'))
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('payment_status')->label(__('ecommerce.payment_status'))->badge(),
                    Infolists\Components\TextEntry::make('total')->label(__('ecommerce.total'))->money('EGP', locale: 'ar'),
                    Infolists\Components\TextEntry::make('created_at')->label(__('ecommerce.created_at'))->dateTime('d/m/Y H:i'),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label(__('ecommerce.order_number'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('ecommerce.customer'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('total')
                    ->label(__('ecommerce.total'))
                    ->money('EGP', locale: 'ar')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('ecommerce.status'))
                    ->badge()
                    ->color(fn ($state) => $state instanceof OrderStatus ? $state->color() : 'gray')
                    ->formatStateUsing(fn ($state) => $state instanceof OrderStatus ? $state->label() : $state),
                Tables\Columns\TextColumn::make('tracking_number')
                    ->label(__('ecommerce.tracking_number'))
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label(__('ecommerce.payment_status'))
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('ecommerce.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('ecommerce.status'))
                    ->options(collect(OrderStatus::cases())->mapWithKeys(
                        fn (OrderStatus $s) => [$s->value => $s->label()]
                    )),
                Tables\Filters\Filter::make('has_tracking')
                    ->label(__('ecommerce.has_tracking'))
                    ->query(fn ($query) => $query->whereNotNull('tracking_number')->where('tracking_number', '!=', '')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('set_tracking')
                    ->label(__('ecommerce.set_tracking'))
                    ->icon('heroicon-o-truck')
                    ->form([
                        Forms\Components\TextInput::make('tracking_number')
                            ->label(__('ecommerce.tracking_number'))
                            ->required(),
                    ])
                    ->action(function (Order $record, array $data, OrderService $orders): void {
                        $orders->setTrackingNumber($record, $data['tracking_number'], null, auth()->user());
                        Notification::make()->title(__('ecommerce.tracking_updated'))->success()->send();
                    }),
                Tables\Actions\Action::make('print_invoice')
                    ->label(__('ecommerce.print_invoice'))
                    ->icon('heroicon-o-printer')
                    ->url(fn (Order $record): string => OrderInvoiceController::printUrl($record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('download_invoice')
                    ->label(__('ecommerce.download_invoice'))
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (Order $record): string => OrderInvoiceController::downloadUrl($record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            OrderItemsRelationManager::class,
            StatusHistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
