<?php

namespace App\Filament\Resources\DriverVehicleAssignments\Schemas;

use App\Models\User;
use App\Models\Vehicle;
use App\Rules\UniqueActiveAssignment;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class DriverVehicleAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('driver_id')
                ->label('Driver')
                ->options(function () {
                    // Use the Spatie scope "role" to select users that have the Driver role
                    // If you prefer case-insensitive, ensure the role is created as 'Driver'
                    return User::role('Driver')->pluck('name', 'id');
                })
                ->searchable()
                ->required()
                ->rules([
                    new UniqueActiveAssignment('driver', request()->route('record')),
                ]),

            Select::make('vehicle_id')
                ->label('Vehicle')
                ->options(function () {
                    return Vehicle::orderBy('plate_number')->pluck('plate_number', 'id');
                })
                ->searchable()
                ->required()
                ->rules([
                    new UniqueActiveAssignment('vehicle', request()->route('record')),
                ]),

            DateTimePicker::make('start_date')
                ->label('Start date')
                ->default(now())
                ->required(),

            DateTimePicker::make('end_date')
                ->label('End date')
                ->nullable(),
        ]);
    }
}
