<?php

namespace App\Filament\Resources\VehicleInspections\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VehicleInspectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('vehicle.plate_number')->label('Vehicle'),
                TextColumn::make('scheduled_date')->date(),
                TextColumn::make('completed_date')->date(),
                TextColumn::make('status')->badge(),
                TextColumn::make('document_path')
                    ->url(fn ($record) => $record->document_path ? asset('storage/' . $record->document_path) : null)
                    ->label('Document')
                    ->openUrlInNewTab(),
            ]);
    }
}
