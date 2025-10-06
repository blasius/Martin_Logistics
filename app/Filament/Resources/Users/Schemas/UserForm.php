<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Filament\Forms\Components\CheckboxList;


class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),

                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->required(),

                DateTimePicker::make('email_verified_at'),

                Select::make('roles')
                    ->label('Roles')
                    ->multiple()
                    ->options(Role::all()->pluck('name', 'name'))
                    ->preload()
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) =>
                    $set('permissions', Role::whereIn('name', $state)
                        ->with('permissions')
                        ->get()
                        ->pluck('permissions.*.name')
                        ->flatten()
                        ->unique()
                        ->values()
                        ->toArray()
                    )
                    ),
            ]);
    }
}
