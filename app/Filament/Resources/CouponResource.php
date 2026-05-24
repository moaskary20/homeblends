<?php

namespace App\Filament\Resources;

use App\Enums\CouponType;
use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.sales');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.coupons');
    }

    public static function getModelLabel(): string
    {
        return __('ecommerce.coupon');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ecommerce.coupons');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')
                ->label(__('ecommerce.coupon_code'))
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(50),
            Forms\Components\Select::make('type')
                ->label(__('ecommerce.coupon_type'))
                ->options(collect(CouponType::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]))
                ->required(),
            Forms\Components\TextInput::make('value')
                ->label(__('ecommerce.coupon_value'))
                ->numeric()
                ->required(),
            Forms\Components\TextInput::make('min_cart_amount')
                ->label(__('ecommerce.min_cart_amount'))
                ->numeric()
                ->prefix('ج.م'),
            Forms\Components\TextInput::make('usage_limit')
                ->label(__('ecommerce.usage_limit'))
                ->numeric(),
            Forms\Components\TextInput::make('usage_per_user')
                ->label(__('ecommerce.usage_per_user'))
                ->numeric(),
            Forms\Components\DateTimePicker::make('starts_at')->label(__('ecommerce.starts_at')),
            Forms\Components\DateTimePicker::make('expires_at')->label(__('ecommerce.expires_at')),
            Forms\Components\Toggle::make('is_active')->label(__('ecommerce.is_active'))->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label(__('ecommerce.coupon_code'))->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('ecommerce.coupon_type'))
                    ->formatStateUsing(fn ($state) => $state instanceof CouponType ? $state->label() : $state),
                Tables\Columns\TextColumn::make('value')->label(__('ecommerce.coupon_value')),
                Tables\Columns\TextColumn::make('used_count')->label(__('ecommerce.used_count')),
                Tables\Columns\TextColumn::make('expires_at')->label(__('ecommerce.expires_at'))->dateTime('d/m/Y'),
                Tables\Columns\IconColumn::make('is_active')->label(__('ecommerce.is_active'))->boolean(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
