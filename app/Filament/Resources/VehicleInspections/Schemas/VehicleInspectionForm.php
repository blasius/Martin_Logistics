<?php

namespace App\Filament\Resources\VehicleInspections\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VehicleInspectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('vehicle_id')
                ->relationship('vehicle', 'plate_number') // adjust field
                ->required(),

            DatePicker::make('scheduled_date'),

            DatePicker::make('completed_date'),

            TextInput::make('inspector_name'),

            FileUpload::make('document_path')
                ->disk('public')
                ->directory('inspections')
                ->required(fn ($get) => $get('status') === 'completed'),

            Select::make('status')
                ->options([
                    'pending' => 'Pending',
                    'completed' => 'Completed',
                    'expired' => 'Expired',
                ])
                ->default('pending')
                ->required(),
        ]);
    }
}
