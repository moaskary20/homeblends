<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VipLevelResource\Pages;
use App\Models\VipLevel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VipLevelResource extends Resource
{
    protected static ?string $model = VipLevel::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.loyalty_program');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.vip_levels');
    }

    public static function getModelLabel(): string
    {
        return __('ecommerce.vip_level');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && ($user->is_admin || $user->can('loyalty.manage'));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label(__('ecommerce.name'))
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('slug')
                ->label(__('ecommerce.slug'))
                ->required()
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('min_points')
                ->label(__('ecommerce.min_points'))
                ->numeric()
                ->minValue(0)
                ->required(),
            Forms\Components\TextInput::make('discount_percent')
                ->label(__('ecommerce.vip_discount_percent'))
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->suffix('%')
                ->required(),
            Forms\Components\TextInput::make('sort_order')
                ->label(__('ecommerce.sort_order'))
                ->numeric()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__('ecommerce.name')),
                Tables\Columns\TextColumn::make('min_points')->label(__('ecommerce.min_points')),
                Tables\Columns\TextColumn::make('discount_percent')
                    ->label(__('ecommerce.vip_discount_percent'))
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('users_count')
                    ->label(__('ecommerce.users'))
                    ->counts('users'),
            ])
            ->defaultSort('sort_order')
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
            'index' => Pages\ListVipLevels::route('/'),
            'create' => Pages\CreateVipLevel::route('/create'),
            'edit' => Pages\EditVipLevel::route('/{record}/edit'),
        ];
    }
}
