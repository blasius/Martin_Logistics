<?php

namespace App\Filament\Resources\VehicleInsurances\Pages;

use App\Filament\Resources\VehicleInsurances\VehicleInsuranceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVehicleInsurances extends ListRecords
{
    protected static string $resource = VehicleInsuranceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
