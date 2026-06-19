<?php

namespace App\Filament\Resources\StockLevels\Schemas;

use App\Models\Part;
use App\Models\Warehouse;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StockLevelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('warehouse_id')
                ->label('Warehouse')
                ->options(Warehouse::pluck('name', 'id'))
                ->required(),
            Select::make('part_id')
                ->label('Part')
                ->options(Part::pluck('name', 'id'))
                ->required(),
            TextInput::make('quantity')
                ->numeric()
                ->required()
                ->default(0),
            TextInput::make('min_quantity')
                ->numeric()
                ->required()
                ->default(0),
        ]);
    }
}
