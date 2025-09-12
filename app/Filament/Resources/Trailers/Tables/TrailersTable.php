<?php

namespace App\Filament\Resources\Trailers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TrailersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plate_number')->searchable()->sortable(),
                TextColumn::make('chassis_number'),
                TextColumn::make('capacity_weight')->label('Capacity'),
                TextColumn::make('type')->sortable(),
                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'in_maintenance',
                    ]),
            ])
            ->filters([])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
