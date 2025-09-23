<?php

namespace App\Filament\Resources\Currencies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CurrenciesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->sortable(),
            TextColumn::make('name')->sortable(),
            TextColumn::make('symbol'),
            IconColumn::make('is_default')->boolean(),
            TextColumn::make('created_at')->dateTime(),
        ])
            ->actions([EditAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }
}
