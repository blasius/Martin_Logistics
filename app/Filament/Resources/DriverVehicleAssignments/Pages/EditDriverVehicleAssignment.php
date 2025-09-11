<?php

namespace App\Filament\Resources\DriverVehicleAssignments\Pages;

use App\Filament\Resources\DriverVehicleAssignments\DriverVehicleAssignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDriverVehicleAssignment extends EditRecord
{
    protected static string $resource = DriverVehicleAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
