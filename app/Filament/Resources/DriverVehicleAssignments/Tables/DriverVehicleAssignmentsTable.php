<?php

namespace App\Filament\Resources\DriverVehicleAssignments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DriverVehicleAssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('driver.name')->label('Driver')->sortable()->searchable(),
            TextColumn::make('vehicle.plate_number')->label('Vehicle')->sortable()->searchable(),
            TextColumn::make('start_date')->dateTime(),
            TextColumn::make('end_date')->dateTime()->toggleable(),
        ]);
    }
}
