<?php

namespace App\Filament\Resources\Requisitions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RequisitionsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('reference')->label('Ref'),
            TextColumn::make('requester.name')->label('Requester'),
            TextColumn::make('expenseType.name')->label('Type'),
            TextColumn::make('amount')->money('USD'), // adjust code as needed
            TextColumn::make('status')->sortable(),
            TextColumn::make('voucher_number'),
            TextColumn::make('created_at')->since(),
        ])->actions([
            EditAction::make(),
            // Approve actions should be implemented in pages (buttons), or via custom actions calling RequisitionService
        ])->filters([]);
    }
}
