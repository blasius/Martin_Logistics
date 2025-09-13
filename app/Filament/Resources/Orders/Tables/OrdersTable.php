<?php

namespace App\Filament\Resources\Orders\Tables;

use DB;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->label('Reference')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('client.name')
                    ->label('Client')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('origin')->sortable()->searchable(),
                TextColumn::make('destination')->sortable()->searchable(),

                TextColumn::make('pickup_date')
                    ->label('Pickup Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('status')->sortable(),
                TextColumn::make('price')
                    ->money('usd', true)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('year')
                    ->label('Year')
                    ->options(function () {
                        $years = DB::table('orders')
                            ->selectRaw('YEAR(created_at) as year')
                            ->distinct()
                            ->orderByDesc('year')
                            ->pluck('year', 'year')
                            ->toArray();

                        return ['all' => 'All Years'] + $years;
                    })
                    ->query(function ($query, $value) {
                        if ($value && $value !== 'all') {
                            $query->whereYear('created_at', $value);
                        }
                    }),
            ])
            ->actions([
                ViewAction::make(),
            ]);
    }
}
