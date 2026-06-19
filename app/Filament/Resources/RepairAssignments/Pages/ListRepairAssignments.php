<?php

namespace App\Filament\Resources\RepairAssignments\Pages;

use App\Filament\Resources\RepairAssignments\RepairAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRepairAssignments extends ListRecords
{
    protected static string $resource = RepairAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
