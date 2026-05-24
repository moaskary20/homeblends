<?php

namespace App\Filament\Affiliate\Resources;

use App\Filament\Affiliate\Resources\PayoutResource\Pages;
use App\Models\AffiliatePayout;
use App\Services\Affiliate\AffiliatePayoutService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PayoutResource extends Resource
{
    protected static ?string $model = AffiliatePayout::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.affiliate_payouts');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('affiliate_id', auth()->user()->affiliate?->id);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('amount')
                ->label(__('ecommerce.amount'))
                ->numeric()
                ->required()
                ->minValue(config('affiliate.min_payout_amount'))
                ->maxValue(fn () => auth()->user()->affiliate?->balance),
            Forms\Components\Textarea::make('notes')->label(__('ecommerce.notes')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('amount')->money('EGP', locale: 'ar'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d/m/Y'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayouts::route('/'),
            'create' => Pages\CreatePayout::route('/create'),
        ];
    }
}
