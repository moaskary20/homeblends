<?php

namespace App\Filament\Resources;

use App\Enums\PaymentGateway as PaymentGatewayDriver;
use App\Filament\Resources\PaymentGatewayResource\Pages;
use App\Models\PaymentGateway;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentGatewayResource extends Resource
{
    protected static ?string $model = PaymentGateway::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.sales');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.payment_gateways');
    }

    public static function getModelLabel(): string
    {
        return __('ecommerce.payment_gateway');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->is_admin || $user->can('payment_gateways.manage'));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('ecommerce.payment_gateway_details'))
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->label(__('ecommerce.payment_gateway_code'))
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\TextInput::make('name')
                        ->label(__('ecommerce.name'))
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Textarea::make('description')
                        ->label(__('ecommerce.description'))
                        ->rows(2),
                    Forms\Components\Textarea::make('instructions')
                        ->label(__('ecommerce.payment_gateway_instructions'))
                        ->rows(3)
                        ->helperText(__('ecommerce.payment_gateway_instructions_hint')),
                    Forms\Components\Toggle::make('is_active')
                        ->label(__('ecommerce.is_active')),
                    Forms\Components\TextInput::make('sort_order')
                        ->label(__('ecommerce.sort_order'))
                        ->numeric()
                        ->default(0),
                ])
                ->columns(2),
            Forms\Components\Section::make(__('ecommerce.payment_gateway_cod_settings'))
                ->schema([
                    Forms\Components\TextInput::make('config.cod_fee')
                        ->label(__('ecommerce.cod_fee'))
                        ->numeric()
                        ->minValue(0)
                        ->prefix('ج.م')
                        ->helperText(__('ecommerce.cod_fee_hint')),
                    Forms\Components\TextInput::make('config.min_order_amount')
                        ->label(__('ecommerce.cod_min_order'))
                        ->numeric()
                        ->minValue(0)
                        ->prefix('ج.م'),
                    Forms\Components\TextInput::make('config.max_order_amount')
                        ->label(__('ecommerce.cod_max_order'))
                        ->numeric()
                        ->minValue(0)
                        ->prefix('ج.م'),
                ])
                ->columns(3)
                ->visible(fn (?PaymentGateway $record): bool => $record?->code === PaymentGatewayDriver::CashOnDelivery->value),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__('ecommerce.name'))->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->label(__('ecommerce.payment_gateway_code'))
                    ->formatStateUsing(fn (string $state) => PaymentGatewayDriver::tryFrom($state)?->label() ?? $state),
                Tables\Columns\TextColumn::make('config.cod_fee')
                    ->label(__('ecommerce.cod_fee'))
                    ->formatStateUsing(fn ($state, PaymentGateway $record) => $record->code === PaymentGatewayDriver::CashOnDelivery->value
                        ? number_format((float) ($state ?? 0), 2).' ج.م'
                        : '—'),
                Tables\Columns\IconColumn::make('is_active')->label(__('ecommerce.is_active'))->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->label(__('ecommerce.sort_order')),
            ])
            ->defaultSort('sort_order')
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentGateways::route('/'),
            'edit' => Pages\EditPaymentGateway::route('/{record}/edit'),
        ];
    }
}
