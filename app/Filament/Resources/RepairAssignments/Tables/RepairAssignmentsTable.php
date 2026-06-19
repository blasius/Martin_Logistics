<?php

namespace App\Filament\Resources\RepairAssignments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RepairAssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('repairRequest.reference')->searchable()->sortable(),
                TextColumn::make('mechanic.name')->searchable()->sortable(),
                TextColumn::make('assigned_at')->dateTime(),
                TextColumn::make('started_at')->dateTime(),
                TextColumn::make('completed_at')->dateTime(),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}
