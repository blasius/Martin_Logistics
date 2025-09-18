<?php

namespace App\Filament\Resources\WialonUnits\Schemas;

use App\Models\Vehicle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WialonUnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('wialon_id')->disabled(),
            TextInput::make('name'),
            Select::make('vehicle_id')
                ->label('Linked Vehicle')
                ->options(Vehicle::pluck('plate_number','id'))
                ->searchable()
                ->nullable(),
            Toggle::make('is_linked'),
        ]);
    }
}
