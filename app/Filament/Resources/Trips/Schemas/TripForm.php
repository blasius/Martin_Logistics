<?php

namespace App\Filament\Resources\Trips\Schemas;

use App\Models\Driver;
use App\Models\Order;
use App\Models\User;
use App\Models\Vehicle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class TripForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('order_id')
                ->relationship('order', 'reference')
                ->required(),

            Select::make('assignment')
                ->label('Vehicle or Driver')
                ->searchable()
                ->getSearchResultsUsing(function (string $search) {
                    $vehicles = Vehicle::where('plate_number', 'like', "%{$search}%")
                        ->limit(10)
                        ->get();

                    $drivers = Driver::with('user')
                        ->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                        ->limit(10)
                        ->get();

                    $results = [];
                    foreach ($vehicles as $vehicle) {
                        $results['vehicle-' . $vehicle->id] = 'Vehicle: ' . $vehicle->plate_number;
                    }
                    foreach ($drivers as $driver) {
                        $results['driver-' . $driver->id] = 'Driver: ' . $driver->user->name;
                    }
                    return $results;
                })
                ->getOptionLabelUsing(function ($value): ?string {
                    if (str_starts_with($value, 'vehicle-')) {
                        $id = substr($value, strlen('vehicle-'));
                        $vehicle = Vehicle::find($id);
                        return $vehicle ? 'Vehicle: ' . $vehicle->plate_number : null;
                    } elseif (str_starts_with($value, 'driver-')) {
                        $id = substr($value, strlen('driver-'));
                        $driver = Driver::with('user')->find($id);
                        return $driver ? 'Driver: ' . $driver->user->name : null;
                    }
                    return null;
                })
                ->required()
                ->live(onBlur: true),

            Hidden::make('vehicle_id')->dehydrated(true),
            Hidden::make('driver_id')->dehydrated(true),

            Select::make('status')
                ->options([
                    'pending' => 'Pending',
                    'assigned' => 'Assigned',
                    'on_route' => 'On Route',
                    'delivered' => 'Delivered',
                    'cancelled' => 'Cancelled',
                ])
                ->default('pending')
                ->required(),

            DateTimePicker::make('departure_time'),
            DateTimePicker::make('arrival_time'),
        ]);
    }
}
