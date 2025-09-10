<?php

namespace App\Filament\Resources\Drivers\Schemas;

use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DriverForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('user_id')
                ->label('User')
                ->options(User::whereHas('roles', fn($q) => $q->where('name', 'driver'))
                    ->pluck('name', 'id'))
                ->searchable()
                ->required(),

            TextInput::make('phone')->tel(),
            TextInput::make('whatsapp_phone')->tel(),
            TextInput::make('passport_number'),
            TextInput::make('driving_licence'),
            TextInput::make('nationality'),
            Select::make('sex')
                ->options(['male' => 'Male', 'female' => 'Female']),
            DatePicker::make('date_of_birth'),
        ]);
    }
}
