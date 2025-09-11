<?php

namespace App\Filament\Resources\Vehicles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VehiclesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('plate_number')->sortable()->searchable(),
            TextColumn::make('make')->searchable(),
            TextColumn::make('model')->searchable(),
            TextColumn::make('year')->sortable(),
            TextColumn::make('color'),
            TextColumn::make('capacity')
                ->label('Capacity')
                ->formatStateUsing(fn ($state, $record) => $state . ' ' . $record->capacity_unit)
                ->sortable(),
            BadgeColumn::make('status')
                ->colors([
                    'success' => 'active',
                    'warning' => 'maintenance',
                    'danger' => 'inactive',
                ]),
        ]);
    }
}
