<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.roles');
    }

    public static function getModelLabel(): string
    {
        return __('ecommerce.role');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ecommerce.roles');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && ($user->is_admin || $user->can('roles.manage'));
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return static::canViewAny() && $record->name !== 'admin';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label(__('ecommerce.role_name'))
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->disabled(fn (?Role $record): bool => $record?->name === 'admin'),
            Forms\Components\Hidden::make('guard_name')
                ->default('web'),
            Forms\Components\CheckboxList::make('permissions')
                ->label(__('ecommerce.permissions'))
                ->relationship('permissions', 'name')
                ->options(fn () => Permission::query()->orderBy('name')->pluck('name', 'id'))
                ->columns(2)
                ->searchable()
                ->bulkToggleable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('ecommerce.role_name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->label(__('ecommerce.permissions'))
                    ->counts('permissions'),
                Tables\Columns\TextColumn::make('users_count')
                    ->label(__('ecommerce.users'))
                    ->counts('users'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('ecommerce.created_at'))
                    ->dateTime('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Role $record): bool => $record->name !== 'admin'),
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
