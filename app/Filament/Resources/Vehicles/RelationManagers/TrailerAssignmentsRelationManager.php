<?php

namespace App\Filament\Resources\Vehicles\RelationManagers;

use App\Models\Trailer;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TrailerAssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'trailerAssignments';
    protected static ?string $title = 'Trailer History';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('trailer_id')
                ->label('Trailer')
                ->options(Trailer::query()->pluck('plate_number', 'id'))
                ->searchable()
                ->required(),

            DateTimePicker::make('assigned_at')
                ->default(now())
                ->required(),

            DateTimePicker::make('unassigned_at')
                ->nullable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('trailer.plate_number')
                    ->label('Trailer Plate'),

                TextColumn::make('attached_at')->date(),

                TextColumn::make('unassigned_at')
                    ->date()
                    ->placeholder('Still attached'),
            ])
            ->headerActions([
                CreateAction::make(),   // 👈 This adds the "Create" button
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

}
