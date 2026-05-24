<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReturnRequestResource\Pages;
use App\Models\ReturnRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReturnRequestResource extends Resource
{
    protected static ?string $model = ReturnRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?int $navigationSort = 3;

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
        return __('ecommerce.return_requests');
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
            Forms\Components\KeyValue::make('items')
                ->label(__('ecommerce.order_items'))
                ->disabled(),
            Forms\Components\Textarea::make('reason')->label(__('ecommerce.reason'))->columnSpanFull(),
            Forms\Components\Select::make('status')
                ->label(__('ecommerce.status'))
                ->options([
                    'pending' => __('ecommerce.return_pending'),
                    'approved' => __('ecommerce.return_approved'),
                    'rejected' => __('ecommerce.return_rejected'),
                    'received' => __('ecommerce.return_received'),
                    'completed' => __('ecommerce.return_completed'),
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
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (ReturnRequest $record) => $record->statusLabel()),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d/m/Y H:i'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label(__('ecommerce.approve'))
                    ->color('success')
                    ->visible(fn (ReturnRequest $record) => $record->status === 'pending')
                    ->action(fn (ReturnRequest $record) => static::setStatus($record, 'approved')),
                Tables\Actions\Action::make('mark_received')
                    ->label(__('ecommerce.return_received'))
                    ->visible(fn (ReturnRequest $record) => $record->status === 'approved')
                    ->action(fn (ReturnRequest $record) => static::setStatus($record, 'received')),
                Tables\Actions\Action::make('complete')
                    ->label(__('ecommerce.return_completed'))
                    ->visible(fn (ReturnRequest $record) => in_array($record->status, ['approved', 'received'], true))
                    ->action(fn (ReturnRequest $record) => static::setStatus($record, 'completed')),
                Tables\Actions\Action::make('reject')
                    ->label(__('ecommerce.reject'))
                    ->color('danger')
                    ->visible(fn (ReturnRequest $record) => $record->status === 'pending')
                    ->action(fn (ReturnRequest $record) => static::setStatus($record, 'rejected')),
            ]);
    }

    public static function setStatus(ReturnRequest $record, string $status): void
    {
        $record->update(['status' => $status]);
        Notification::make()->title(__('ecommerce.return_status_updated'))->success()->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReturnRequests::route('/'),
            'edit' => Pages\EditReturnRequest::route('/{record}/edit'),
        ];
    }
}
