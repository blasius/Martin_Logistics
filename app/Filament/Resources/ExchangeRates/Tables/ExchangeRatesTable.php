<?php

namespace App\Filament\Resources\ExchangeRates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExchangeRatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('base_currency')->sortable(),
                TextColumn::make('target_currency')->sortable(),
                TextColumn::make('rate')->sortable(),
                TextColumn::make('valid_from')->dateTime()->sortable(),
                TextColumn::make('valid_to')->dateTime()->sortable(),
            ])
            ->filters([])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}
