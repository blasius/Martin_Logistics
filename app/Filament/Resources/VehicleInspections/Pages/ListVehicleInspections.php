<?php

namespace App\Filament\Resources\VehicleInspections\Pages;

use App\Filament\Resources\VehicleInspections\VehicleInspectionResource;
use App\Filament\Widgets\VehicleInspectionCalendar;
use App\Livewire\InspectionsCalendarWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVehicleInspections extends ListRecords
{
    protected static string $resource = VehicleInspectionResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            InspectionsCalendarWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
