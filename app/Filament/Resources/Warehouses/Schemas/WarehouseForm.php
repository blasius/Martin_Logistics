<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('code')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(50),
            TextInput::make('location')
                ->maxLength(255),
            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ]);
    }
}
