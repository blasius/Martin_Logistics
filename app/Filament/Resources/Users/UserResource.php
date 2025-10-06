<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $recordTitleAttribute = 'name';
    //protected static string|null|\UnitEnum $navigationGroup = null; // top-level menu
    protected static ?int $navigationSort = 2; // ensures it’s the second item in the sidebar

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        // Remove non-user table fields
        unset($data['roles'], $data['permissions']);
        return $data;
    }

    public static function afterSave(Model $record, array $data): void
    {
        if (isset($data['roles'])) {
            $record->syncRoles($data['roles']);
        }

        if (isset($data['permissions'])) {
            $record->syncPermissions($data['permissions']);
        }
    }

}
