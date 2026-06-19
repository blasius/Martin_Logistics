<?php

namespace App\Filament\Resources\RepairReleases\Schemas;

use App\Models\RepairRequest;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RepairReleaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('repair_request_id')
                ->label('Repair Request')
                ->options(RepairRequest::pluck('reference', 'id'))
                ->required(),
            Select::make('released_by')
                ->label('Released By')
                ->options(User::pluck('name', 'id'))
                ->required(),
            DateTimePicker::make('released_at')
                ->required(),
            TextInput::make('odometer_at_release')
                ->numeric()
                ->nullable(),
            Textarea::make('unresolved_issues')
                ->rows(3)
                ->nullable(),
            Toggle::make('checklist_completed')
                ->label('Checklist Completed')
                ->default(false),
        ]);
    }
}
