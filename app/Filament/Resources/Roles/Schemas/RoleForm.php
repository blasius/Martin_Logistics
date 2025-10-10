<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Models\Permission;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label('Role Name')
                    ->required()
                    ->unique(ignoreRecord: true),

                CheckboxList::make('permissions')
                    ->label('Assign Permissions')
                    ->relationship('permissions', 'name')
                    ->columns(2)
                    ->bulkToggleable()
                    ->options(
                        Permission::all()->pluck('name', 'id')
                    ),
            ]);
    }
}
