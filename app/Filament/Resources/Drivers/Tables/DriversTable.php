<?php

namespace App\Filament\Resources\Drivers\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DriversTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('Name'),
                TextColumn::make('phone'),
                TextColumn::make('driving_licence')->label('Licence'),
                TextColumn::make('nationality'),
                TextColumn::make('sex'),
            ])
            ->filters([]);
    }
}
