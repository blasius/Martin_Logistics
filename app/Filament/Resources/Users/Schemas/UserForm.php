<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

use Illuminate\Support\Facades\Hash;
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

                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create'),

                DateTimePicker::make('email_verified_at'),

                // 👇 NEW: Assign roles dynamically
                Select::make('roles')
                    ->label('Roles')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload()
                    ->searchable()
                    ->helperText('Select one or more roles for this user'),

                // 👇 OPTIONAL: Direct permission overrides
                Select::make('permissions')
                    ->label('Direct Permissions')
                    ->multiple()
                    ->relationship('permissions', 'name')
                    ->preload()
                    ->searchable()
                    ->helperText('Assign specific permissions (optional)'),
            ]);
    }
}
