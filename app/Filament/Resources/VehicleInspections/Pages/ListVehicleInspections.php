<?php

namespace App\Filament\Resources\VehicleInspections\Pages;

use App\Filament\Resources\VehicleInspections\VehicleInspectionResource;
use App\Filament\Widgets\VehicleInspectionCalendar;
use Filament\Resources\Pages\ListRecords;

class ListVehicleInspections extends ListRecords
{
    protected static string $resource = VehicleInspectionResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            VehicleInspectionCalendar::class,
        ];
    }
}
