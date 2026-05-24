<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers\LoyaltyTransactionsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\OrdersRelationManager;
use App\Models\User;
use App\Models\VipLevel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.users');
    }

    public static function getModelLabel(): string
    {
        return __('ecommerce.user');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ecommerce.users');
    }

    public static function canViewAny(): bool
    {
        return static::canManageUsers();
    }

    public static function canCreate(): bool
    {
        return static::canManageUsers();
    }

    public static function canEdit($record): bool
    {
        return static::canManageUsers();
    }

    public static function canDelete($record): bool
    {
        return static::canManageUsers() && ! static::isProtectedUser($record);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('ecommerce.user_profile'))
                ->schema([
                    Forms\Components\FileUpload::make('avatar')
                        ->label(__('ecommerce.avatar'))
                        ->image()
                        ->directory('avatars')
                        ->avatar(),
                    Forms\Components\TextInput::make('name')
                        ->label(__('ecommerce.name'))
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->label(__('ecommerce.email'))
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    Forms\Components\TextInput::make('phone')
                        ->label(__('ecommerce.phone'))
                        ->tel()
                        ->maxLength(30),
                ])
                ->columns(2),

            Forms\Components\Section::make(__('ecommerce.user_security'))
                ->schema([
                    Forms\Components\TextInput::make('password')
                        ->label(__('ecommerce.password'))
                        ->password()
                        ->revealable()
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->maxLength(255)
                        ->confirmed(),
                    Forms\Components\TextInput::make('password_confirmation')
                        ->label(__('ecommerce.password_confirmation'))
                        ->password()
                        ->revealable()
                        ->dehydrated(false)
                        ->required(fn (string $operation): bool => $operation === 'create'),
                    Forms\Components\DateTimePicker::make('email_verified_at')
                        ->label(__('ecommerce.email_verified_at'))
                        ->native(false),
                ])
                ->columns(2),

            Forms\Components\Section::make(__('ecommerce.user_access'))
                ->schema([
                    Forms\Components\Toggle::make('is_admin')
                        ->label(__('ecommerce.is_admin'))
                        ->helperText(__('ecommerce.is_admin_help')),
                    Forms\Components\Select::make('roles')
                        ->label(__('ecommerce.roles'))
                        ->relationship('roles', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable(),
                ])
                ->columns(2),

            Forms\Components\Section::make(__('ecommerce.user_commerce'))
                ->schema([
                    Forms\Components\Select::make('vip_level_id')
                        ->label(__('ecommerce.vip_level'))
                        ->options(fn () => VipLevel::query()->orderBy('sort_order')->pluck('name', 'id'))
                        ->searchable()
                        ->nullable(),
                    Forms\Components\TextInput::make('loyalty_points')
                        ->label(__('ecommerce.loyalty_points'))
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    Forms\Components\TextInput::make('store_credit')
                        ->label(__('ecommerce.store_credit'))
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01)
                        ->default(0),
                    Forms\Components\Select::make('locale')
                        ->label(__('ecommerce.locale'))
                        ->options(['ar' => 'العربية'])
                        ->default('ar')
                        ->required(),
                    Forms\Components\Select::make('currency')
                        ->label(__('ecommerce.currency'))
                        ->options(['EGP' => 'جنيه مصري (EGP)'])
                        ->default('EGP')
                        ->required(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label(__('ecommerce.avatar'))
                    ->circular()
                    ->defaultImageUrl(fn (User $record) => 'https://ui-avatars.com/api/?name='.urlencode($record->name)),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('ecommerce.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('ecommerce.email'))
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('ecommerce.phone'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label(__('ecommerce.roles'))
                    ->badge()
                    ->separator(','),
                Tables\Columns\IconColumn::make('is_admin')
                    ->label(__('ecommerce.is_admin'))
                    ->boolean(),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label(__('ecommerce.email_verified'))
                    ->boolean()
                    ->getStateUsing(fn (User $record): bool => $record->email_verified_at !== null),
                Tables\Columns\TextColumn::make('orders_count')
                    ->label(__('ecommerce.orders'))
                    ->counts('orders')
                    ->sortable(),
                Tables\Columns\TextColumn::make('loyalty_points')
                    ->label(__('ecommerce.loyalty_points'))
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('ecommerce.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_admin')
                    ->label(__('ecommerce.is_admin')),
                Tables\Filters\Filter::make('verified')
                    ->label(__('ecommerce.email_verified'))
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('email_verified_at')),
                Tables\Filters\SelectFilter::make('roles')
                    ->label(__('ecommerce.role'))
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('verify_email')
                    ->label(__('ecommerce.verify_email'))
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (User $record): bool => $record->email_verified_at === null)
                    ->action(function (User $record): void {
                        $record->update(['email_verified_at' => now()]);
                        Notification::make()
                            ->title(__('ecommerce.email_verified_success'))
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tables\Actions\DeleteAction $action, User $record): void {
                        if (static::isProtectedUser($record)) {
                            Notification::make()
                                ->title(__('ecommerce.cannot_delete_admin'))
                                ->danger()
                                ->send();
                            $action->halt();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Tables\Actions\DeleteBulkAction $action, $records): void {
                            foreach ($records as $record) {
                                if (static::isProtectedUser($record)) {
                                    Notification::make()
                                        ->title(__('ecommerce.cannot_delete_admin'))
                                        ->danger()
                                        ->send();
                                    $action->halt();

                                    return;
                                }
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            OrdersRelationManager::class,
            LoyaltyTransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canManageUsers(): bool
    {
        $user = auth()->user();

        return $user instanceof User && ($user->is_admin || $user->can('users.manage'));
    }

    public static function isProtectedUser(User $record): bool
    {
        if ($record->id === auth()->id()) {
            return true;
        }

        if (! $record->is_admin && ! $record->hasRole('admin')) {
            return false;
        }

        $adminCount = User::query()
            ->where(function (Builder $query): void {
                $query->where('is_admin', true)
                    ->orWhereHas('roles', fn (Builder $q) => $q->where('name', 'admin'));
            })
            ->count();

        return $adminCount <= 1;
    }

    public static function syncAdminRole(User $user): void
    {
        $adminRole = Role::findByName('admin', 'web');

        if ($user->is_admin) {
            $user->assignRole($adminRole);

            return;
        }

        if ($user->hasRole('admin')) {
            $user->removeRole($adminRole);
        }
    }
}
