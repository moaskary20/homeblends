<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Filament\Resources\RefundRequestResource\Pages;
use App\Models\RefundRequest;
use App\Services\Order\OrderService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RefundRequestResource extends Resource
{
    protected static ?string $model = RefundRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.sales');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.refund_requests');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && ($user->is_admin || $user->can('orders.manage'));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('order.order_number')
                ->label(__('ecommerce.order_number'))
                ->disabled(),
            Forms\Components\TextInput::make('amount')
                ->label(__('ecommerce.amount'))
                ->numeric()
                ->prefix('ج.م'),
            Forms\Components\Textarea::make('reason')->label(__('ecommerce.reason'))->columnSpanFull(),
            Forms\Components\Select::make('status')
                ->label(__('ecommerce.status'))
                ->options([
                    'pending' => __('ecommerce.refund_pending'),
                    'approved' => __('ecommerce.refund_approved'),
                    'rejected' => __('ecommerce.refund_rejected'),
                    'processed' => __('ecommerce.refund_processed'),
                ])
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.order_number')->label(__('ecommerce.order_number'))->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label(__('ecommerce.customer')),
                Tables\Columns\TextColumn::make('amount')->money('EGP', locale: 'ar'),
                Tables\Columns\TextColumn::make('status')->badge()->label(__('ecommerce.status')),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d/m/Y H:i'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => __('ecommerce.refund_pending'),
                    'approved' => __('ecommerce.refund_approved'),
                    'rejected' => __('ecommerce.refund_rejected'),
                    'processed' => __('ecommerce.refund_processed'),
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label(__('ecommerce.approve'))
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn (RefundRequest $record) => $record->status === 'pending')
                    ->action(fn (RefundRequest $record) => static::updateStatus($record, 'approved')),
                Tables\Actions\Action::make('reject')
                    ->label(__('ecommerce.reject'))
                    ->color('danger')
                    ->visible(fn (RefundRequest $record) => $record->status === 'pending')
                    ->action(fn (RefundRequest $record) => static::updateStatus($record, 'rejected')),
                Tables\Actions\Action::make('process')
                    ->label(__('ecommerce.refund_process'))
                    ->color('warning')
                    ->visible(fn (RefundRequest $record) => $record->status === 'approved')
                    ->requiresConfirmation()
                    ->action(function (RefundRequest $record, OrderService $orders): void {
                        $record->load('order');
                        static::updateStatus($record, 'processed');
                        $orders->updateStatus(
                            $record->order,
                            OrderStatus::Refunded,
                            __('ecommerce.refund_processed_order'),
                            auth()->user()
                        );
                    }),
            ]);
    }

    public static function updateStatus(RefundRequest $record, string $status): void
    {
        $record->update(['status' => $status]);
        Notification::make()->title(__('ecommerce.refund_status_updated'))->success()->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRefundRequests::route('/'),
            'edit' => Pages\EditRefundRequest::route('/{record}/edit'),
        ];
    }
}
