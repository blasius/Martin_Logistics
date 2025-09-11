<?php

namespace App\Filament\Resources\VehicleInspections\Pages;

use App\Filament\Resources\VehicleInspections\VehicleInspectionResource;
use App\Models\VehicleInspection;
use Filament\Resources\Pages\CreateRecord;

class CreateVehicleInspection extends CreateRecord
{
    protected static string $resource = VehicleInspectionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($data['status'] === 'completed') {
            $previous = VehicleInspection::where('vehicle_id', $data['vehicle_id'])
                ->where('status', 'completed')
                ->latest('completed_date')
                ->first();

            if ($previous) {
                $previous->update(['status' => 'expired']);
                $data['replaces_id'] = $previous->id;
            }
        }

        return $data;
    }

}
