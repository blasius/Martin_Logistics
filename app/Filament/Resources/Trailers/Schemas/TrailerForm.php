<?php

namespace App\Filament\Resources\Trailers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TrailerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('plate_number')
                    ->label('Plate Number')
                    ->required()
                    ->unique(ignoreRecord: true),

                TextInput::make('chassis_number')
                    ->label('Chassis Number'),

                TextInput::make('capacity_weight')
                    ->label('Capacity (Weight)')
                    ->numeric(),

                Select::make('type')
                    ->label('Type')
                    ->options([
                        'flatbed' => 'Flatbed',
                        'tanker' => 'Tanker',
                        'refrigerated' => 'Refrigerated',
                        'container' => 'Container',
                        'other' => 'Other',
                    ])
                    ->nullable(),

                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'in_maintenance' => 'In Maintenance',
                    ])
                    ->default('active')
                    ->required(),
            ]);
    }
}
