<?php

namespace App\Filament\Resources\RepairAssignments\Schemas;

use App\Models\RepairRequest;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class RepairAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('repair_request_id')
                ->label('Repair Request')
                ->options(RepairRequest::pluck('reference', 'id'))
                ->required(),
            Select::make('mechanic_id')
                ->label('Mechanic')
                ->options(User::pluck('name', 'id'))
                ->required(),
            DateTimePicker::make('assigned_at')
                ->required(),
            DateTimePicker::make('started_at')
                ->nullable(),
            DateTimePicker::make('completed_at')
                ->nullable(),
        ]);
    }
}
