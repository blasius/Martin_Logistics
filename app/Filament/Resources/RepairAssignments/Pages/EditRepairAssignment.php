<?php

namespace App\Filament\Resources\RepairAssignments\Pages;

use App\Filament\Resources\RepairAssignments\RepairAssignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRepairAssignment extends EditRecord
{
    protected static string $resource = RepairAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
