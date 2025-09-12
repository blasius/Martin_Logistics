<?php

namespace App\Filament\Resources\VehicleInsurances\Schemas;

use App\Models\Vehicle;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VehicleInsuranceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('vehicle_id')
                    ->label('Vehicle')
                    ->options(Vehicle::all()->pluck('plate_number', 'id'))
                    ->searchable()
                    ->required(),

                TextInput::make('policy_number')
                    ->required()
                    ->maxLength(255),

                TextInput::make('provider_name')
                    ->label('Insurance Provider')
                    ->maxLength(255),

                DatePicker::make('issue_date')
                    ->required(),

                DatePicker::make('expiry_date')
                    ->required(),

                FileUpload::make('document_path')
                    ->label('Scanned Document')
                    ->directory('insurances')
                    ->preserveFilenames()
                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                    ->downloadable()
                    ->openable()
                    ->required(),

                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'expired' => 'Expired',
                        'pending' => 'Pending',
                    ])
                    ->default('active'),
            ]);
    }
}
