<?php

namespace App\Filament\Resources\VehicleInsurances\Pages;

use App\Filament\Resources\VehicleInsurances\VehicleInsuranceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVehicleInsurance extends EditRecord
{
    protected static string $resource = VehicleInsuranceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
