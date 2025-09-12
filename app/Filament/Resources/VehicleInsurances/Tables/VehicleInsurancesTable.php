<?php

namespace App\Filament\Resources\VehicleInsurances\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Query\Builder;

class VehicleInsurancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('vehicle.plate_number')->label('Vehicle'),
                TextColumn::make('policy_number'),
                TextColumn::make('provider_name')->label('Provider'),
                TextColumn::make('issue_date')->date(),
                TextColumn::make('expiry_date')->date(),
                BadgeColumn::make('status')
                    ->colors([
                        'danger' => 'expired',
                        'success' => 'active',
                        'warning' => 'pending',
                    ]),
            ])
            ->filters([
                Filter::make('active')->query(fn (Builder $query) => $query->active()),
                Filter::make('expired')->query(fn (Builder $query) => $query->expired()),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
