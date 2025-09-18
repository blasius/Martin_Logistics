<?php

namespace App\Filament\Resources\WialonUnits\Tables;

use App\Models\Vehicle;
use App\Models\WialonUnit;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WialonUnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('wialon_id'),
                TextColumn::make('name'),
                TextColumn::make('vehicle.plate_number')->label('Linked Vehicle'),
                IconColumn::make('is_linked')->boolean(),
            ])
            ->actions([
                EditAction::make(),
                Action::make('link')
                    ->label('Link to Vehicle')
                    ->icon('heroicon-o-link')
                    ->form([
                        Select::make('vehicle_id')
                            ->label('Vehicle')
                            ->options(Vehicle::pluck('plate_number','id'))
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (WialonUnit $record, array $data) {
                        $vehicle = Vehicle::find($data['vehicle_id']);
                        $record->vehicle()->associate($vehicle);
                        $record->is_linked = true;
                        $record->save();
                    }),
                Action::make('unlink')
                    ->label('Unlink')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (WialonUnit $record) {
                        $record->vehicle()->dissociate();
                        $record->is_linked = false;
                        $record->save();
                    }),
            ]);
    }
}
