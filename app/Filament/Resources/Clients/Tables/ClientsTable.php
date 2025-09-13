<?php

namespace App\Filament\Resources\Clients\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('contact_person'),
                TextColumn::make('phone'),
                TextColumn::make('type')->badge(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'company' => 'Company',
                        'individual' => 'Individual',
                    ]),
            ]);
    }
}
