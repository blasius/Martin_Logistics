<?php

namespace App\Filament\Resources\Trips\Pages;

use App\Filament\Resources\Trips\TripResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTrip extends CreateRecord
{
    protected static string $resource = TripResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!empty($data['assignment'])) {
            [$type, $id] = explode('-', $data['assignment']);
            if ($type === 'vehicle') {
                $data['vehicle_id'] = $id;
                $data['driver_id'] = null;
            } elseif ($type === 'driver') {
                $data['driver_id'] = $id;
                $data['vehicle_id'] = null;
            }
        }

        unset($data['assignment']); // not needed in DB

        return $data;
    }

}
