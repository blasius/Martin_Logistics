<?php

namespace App\Filament\Resources\Vehicles\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VehicleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('plate_number')
                ->required()
                ->unique(ignoreRecord: true),

            TextInput::make('make'),
            TextInput::make('model'),
            TextInput::make('year')->numeric()->minValue(1900)->maxValue(now()->year),
            TextInput::make('color'),

            Select::make('status')
                ->options([
                    'active' => 'Active',
                    'maintenance' => 'Maintenance',
                    'inactive' => 'Inactive',
                ])
                ->default('active'),
        ]);
    }
}
