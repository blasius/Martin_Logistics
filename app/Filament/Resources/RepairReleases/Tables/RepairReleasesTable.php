<?php

namespace App\Filament\Resources\RepairReleases\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class RepairReleasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('repairRequest.reference')->searchable()->sortable(),
                TextColumn::make('releasedBy.name')->searchable()->sortable(),
                TextColumn::make('released_at')->dateTime(),
                TextColumn::make('odometer_at_release'),
                IconColumn::make('checklist_completed')
                    ->boolean(),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([
                TernaryFilter::make('checklist_completed'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}
