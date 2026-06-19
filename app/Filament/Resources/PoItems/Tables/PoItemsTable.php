<?php

namespace App\Filament\Resources\PoItems\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PoItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('purchaseOrder.reference')->searchable()->sortable(),
                TextColumn::make('part.name')->searchable(),
                TextColumn::make('quantity')->sortable(),
                TextColumn::make('unit_price')->money('usd'),
                TextColumn::make('total')->money('usd'),
                TextColumn::make('received_quantity')->sortable(),
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
