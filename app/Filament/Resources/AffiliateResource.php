<?php

namespace App\Filament\Resources;

use App\Enums\AffiliateStatus;
use App\Filament\Resources\AffiliateResource\Pages;
use App\Models\Affiliate;
use App\Services\Affiliate\AffiliateService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AffiliateResource extends Resource
{
    protected static ?string $model = Affiliate::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.affiliates');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.affiliates');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && ($user->is_admin || $user->can('affiliates.manage'));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('display_name')->label(__('ecommerce.name'))->required(),
                Forms\Components\TextInput::make('code')->label(__('ecommerce.affiliate_code'))->required()->unique(ignoreRecord: true),
                Forms\Components\Select::make('status')
                    ->label(__('ecommerce.status'))
                    ->options(collect(AffiliateStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]))
                    ->required(),
                Forms\Components\TextInput::make('commission_rate')
                    ->label(__('ecommerce.commission_rate'))
                    ->numeric()
                    ->suffix('%'),
                Forms\Components\TextInput::make('website')->label(__('ecommerce.website'))->url(),
                Forms\Components\Textarea::make('bio')->label(__('ecommerce.bio'))->columnSpanFull(),
                Forms\Components\KeyValue::make('payment_details')->label(__('ecommerce.payment_details'))->columnSpanFull(),
                Forms\Components\Textarea::make('admin_notes')->label(__('ecommerce.admin_notes'))->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_name')->label(__('ecommerce.name'))->searchable(),
                Tables\Columns\TextColumn::make('user.email')->label(__('ecommerce.email'))->searchable(),
                Tables\Columns\TextColumn::make('code')->label(__('ecommerce.affiliate_code'))->copyable(),
                Tables\Columns\TextColumn::make('status')->label(__('ecommerce.status'))->badge(),
                Tables\Columns\TextColumn::make('commission_rate')->label(__('ecommerce.commission_rate'))->suffix('%'),
                Tables\Columns\TextColumn::make('balance')->label(__('ecommerce.balance'))->money('EGP', locale: 'ar'),
                Tables\Columns\TextColumn::make('total_orders')->label(__('ecommerce.total_orders')),
                Tables\Columns\TextColumn::make('total_clicks')->label(__('ecommerce.total_clicks')),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label(__('ecommerce.approve'))
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Affiliate $record) => $record->status === AffiliateStatus::Pending)
                    ->action(function (Affiliate $record): void {
                        app(AffiliateService::class)->approve($record, auth()->user());
                        Notification::make()->title(__('ecommerce.affiliate_approved'))->success()->send();
                    }),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAffiliates::route('/'),
            'edit' => Pages\EditAffiliate::route('/{record}/edit'),
        ];
    }
}
