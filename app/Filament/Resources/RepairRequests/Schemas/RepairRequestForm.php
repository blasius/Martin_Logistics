<?php

namespace App\Filament\Resources\RepairRequests\Schemas;

use App\Models\User;
use App\Models\Vehicle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class RepairRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('reference')
                ->disabled()
                ->maxLength(100),
            Select::make('vehicle_id')
                ->label('Vehicle')
                ->options(Vehicle::pluck('registration_number', 'id'))
                ->required(),
            Select::make('mechanic_id')
                ->label('Mechanic')
                ->options(User::pluck('name', 'id'))
                ->required(),
            Select::make('driver_id')
                ->label('Driver')
                ->options(User::pluck('name', 'id'))
                ->nullable(),
            Select::make('type')
                ->options([
                    'mechanical' => 'Mechanical',
                    'electrical' => 'Electrical',
                    'body' => 'Body',
                    'tire' => 'Tire',
                    'brake' => 'Brake',
                    'other' => 'Other',
                ])
                ->required(),
            Select::make('priority')
                ->options([
                    'low' => 'Low',
                    'medium' => 'Medium',
                    'high' => 'High',
                    'critical' => 'Critical',
                ])
                ->required(),
            Textarea::make('description')
                ->rows(3),
            Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'pending_approval' => 'Pending Approval',
                    'approved' => 'Approved',
                    'parts_pending' => 'Parts Pending',
                    'in_progress' => 'In Progress',
                    'completed' => 'Completed',
                    'released' => 'Released',
                    'cancelled' => 'Cancelled',
                ])
                ->required(),
            DateTimePicker::make('submitted_at'),
        ]);
    }
}
