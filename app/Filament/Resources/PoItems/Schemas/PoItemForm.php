<?php

namespace App\Filament\Resources\PoItems\Schemas;

use App\Models\Part;
use App\Models\PurchaseOrder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PoItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('purchase_order_id')
                ->label('Purchase Order')
                ->options(PurchaseOrder::pluck('reference', 'id'))
                ->required(),
            Select::make('part_id')
                ->label('Part')
                ->options(Part::pluck('name', 'id'))
                ->nullable(),
            Textarea::make('description')
                ->rows(3),
            TextInput::make('quantity')
                ->numeric()
                ->required(),
            TextInput::make('unit_price')
                ->numeric()
                ->prefix('$'),
            TextInput::make('total')
                ->numeric()
                ->prefix('$'),
            TextInput::make('received_quantity')
                ->numeric()
                ->default(0),
        ]);
    }
}
