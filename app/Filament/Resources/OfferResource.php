<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfferResource\Concerns\HasOfferProductForm;
use App\Filament\Resources\OfferResource\Pages;
use App\Models\Offer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OfferResource extends Resource
{
    use HasOfferProductForm;

    protected static ?string $model = Offer::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.sales');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.installment_offers');
    }

    public static function getModelLabel(): string
    {
        return __('ecommerce.installment_offer');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ecommerce.installment_offers');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && ($user->is_admin || $user->can('offers.manage'));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('ecommerce.offer_details'))
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(__('ecommerce.name'))
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Offer::slugify($state ?? ''))),
                    Forms\Components\TextInput::make('slug')
                        ->label(__('ecommerce.slug'))
                        ->required()
                        ->unique(ignoreRecord: true),
                    Forms\Components\Textarea::make('description')
                        ->label(__('ecommerce.description'))
                        ->columnSpanFull(),
                    Forms\Components\DateTimePicker::make('starts_at')
                        ->label(__('ecommerce.starts_at'))
                        ->required(),
                    Forms\Components\DateTimePicker::make('ends_at')
                        ->label(__('ecommerce.ends_at'))
                        ->required()
                        ->after('starts_at'),
                    Forms\Components\CheckboxList::make('installment_plans')
                        ->label(__('ecommerce.installment_plans'))
                        ->options(fn (?Offer $record) => Offer::planFormOptions($record))
                        ->required()
                        ->columns(3)
                        ->default([6])
                        ->columnSpanFull()
                        ->dehydrateStateUsing(fn ($state) => Offer::normalizePlanMonths($state))
                        ->helperText(__('ecommerce.installment_plans_help')),
                    Forms\Components\Toggle::make('is_active')
                        ->label(__('ecommerce.is_active'))
                        ->default(true),
                    Forms\Components\TextInput::make('sort_order')
                        ->label(__('ecommerce.sort_order'))
                        ->numeric()
                        ->default(0),
                    Forms\Components\FileUpload::make('banner_image')
                        ->label(__('ecommerce.banner_image'))
                        ->image()
                        ->directory('offers'),
                    Forms\Components\FileUpload::make('gallery')
                        ->label(__('ecommerce.offer_gallery'))
                        ->image()
                        ->multiple()
                        ->reorderable()
                        ->directory('offers/gallery')
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Forms\Components\Section::make(__('ecommerce.offer_products'))
                ->description(__('ecommerce.offer_products_help'))
                ->schema([
                    static::offerProductsRepeater(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('ecommerce.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('installment_plans')
                    ->label(__('ecommerce.installment_plans'))
                    ->state(fn (Offer $record): string => $record->plansLabel()),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label(__('ecommerce.starts_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label(__('ecommerce.ends_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('products_count')
                    ->label(__('ecommerce.products_count'))
                    ->counts('products'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('ecommerce.status'))
                    ->badge()
                    ->state(fn (Offer $record): string => $record->statusLabel())
                    ->color(fn (Offer $record): string => match (true) {
                        $record->isRunning() => 'success',
                        $record->isUpcoming() => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('ecommerce.is_active'))
                    ->boolean(),
            ])
            ->defaultSort('starts_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label(__('ecommerce.is_active')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOffers::route('/'),
            'create' => Pages\CreateOffer::route('/create'),
            'edit' => Pages\EditOffer::route('/{record}/edit'),
        ];
    }
}
