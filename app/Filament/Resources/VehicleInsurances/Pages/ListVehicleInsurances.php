<?php

namespace App\Filament\Resources\VehicleInsurances\Pages;

use App\Filament\Resources\VehicleInsurances\VehicleInsuranceResource;
use App\Livewire\VehicleInsurancesCalendarWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVehicleInsurances extends ListRecords
{
    protected static string $resource = VehicleInsuranceResource::class;

    public function getHeaderWidgets(): array
    {
        return [
            // Add your calendar widget here
            VehicleInsurancesCalendarWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
