<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FreeShippingRuleResource\Pages;
use App\Models\FreeShippingRule;
use App\Models\ShippingZone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FreeShippingRuleResource extends Resource
{
    protected static ?string $model = FreeShippingRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.shipping');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.free_shipping_rules');
    }

    public static function getModelLabel(): string
    {
        return __('ecommerce.free_shipping_rule');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && ($user->is_admin || $user->can('shipping.manage'));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('shipping_zone_id')
                ->label(__('ecommerce.shipping_zone'))
                ->options(fn () => ShippingZone::query()->pluck('name', 'id'))
                ->searchable()
                ->nullable()
                ->helperText(__('ecommerce.free_shipping_global_help')),
            Forms\Components\TextInput::make('min_order_amount')
                ->label(__('ecommerce.min_order_amount'))
                ->numeric()
                ->required()
                ->prefix('ج.م'),
            Forms\Components\Toggle::make('is_active')
                ->label(__('ecommerce.is_active'))
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('zone.name')
                    ->label(__('ecommerce.shipping_zone'))
                    ->placeholder(__('ecommerce.all_zones')),
                Tables\Columns\TextColumn::make('min_order_amount')
                    ->label(__('ecommerce.min_order_amount'))
                    ->money('EGP', locale: 'ar')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('ecommerce.is_active'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('ecommerce.created_at'))
                    ->dateTime('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFreeShippingRules::route('/'),
            'create' => Pages\CreateFreeShippingRule::route('/create'),
            'edit' => Pages\EditFreeShippingRule::route('/{record}/edit'),
        ];
    }
}
