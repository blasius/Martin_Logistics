<?php

namespace App\Filament\Resources\RepairRequests\Pages;

use App\Filament\Resources\RepairRequests\RepairRequestResource;
use App\Services\RepairRequestService;
use Filament\Resources\Pages\CreateRecord;

class CreateRepairRequest extends CreateRecord
{
    protected static string $resource = RepairRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['reference'] = RepairRequestService::generateReference();

        return $data;
    }
}
