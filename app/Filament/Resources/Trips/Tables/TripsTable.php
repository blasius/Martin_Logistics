<?php

namespace App\Filament\Resources\Trips\Tables;

use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TripsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.reference')->label('Order Ref'),
                TextColumn::make('vehicle.plate_number')
                    ->label('Vehicle')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('driver.user.name')
                    ->label('Driver')
                    ->sortable()
                    ->toggleable(),

                BadgeColumn::make('status')->colors([
                    'danger' => 'cancelled',
                    'warning' => 'pending',
                    'success' => 'delivered',
                    'primary' => 'on_route',
                ]),
                TextColumn::make('departure_time')->dateTime(),
                TextColumn::make('arrival_time')->dateTime(),
                TextColumn::make('creator.name')->label('Created By'),
            ]);
    }
}
