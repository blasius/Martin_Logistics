<?php

namespace App\Filament\Resources\Parts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PartForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('sku')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(100),
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->rows(3),
            Select::make('category')
                ->options([
                    'engine' => 'Engine',
                    'brake' => 'Brake',
                    'electrical' => 'Electrical',
                    'body' => 'Body',
                    'tires' => 'Tires',
                    'other' => 'Other',
                ]),
            TextInput::make('unit_of_measure')
                ->maxLength(50),
            TextInput::make('unit_price')
                ->numeric()
                ->prefix('$'),
            TextInput::make('compatible_vehicle_makes')
                ->maxLength(255),
        ]);
    }
}
