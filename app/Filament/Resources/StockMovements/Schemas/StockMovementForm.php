<?php

namespace App\Filament\Resources\StockMovements\Schemas;

use App\Models\Part;
use App\Models\User;
use App\Models\Warehouse;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class StockMovementForm
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
                ->required(),
            Select::make('type')
                ->options([
                    'in' => 'In',
                    'out' => 'Out',
                    'adjust' => 'Adjust',
                    'transfer' => 'Transfer',
                ])
                ->required(),
            TextInput::make('reference_type')
                ->maxLength(100),
            TextInput::make('reference_id')
                ->numeric(),
            Select::make('user_id')
                ->label('User')
                ->options(User::pluck('name', 'id'))
                ->required(),
            Textarea::make('notes')
                ->rows(3),
        ]);
    }
}
