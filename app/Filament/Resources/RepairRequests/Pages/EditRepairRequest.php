<?php

namespace App\Filament\Resources\RepairRequests\Pages;

use App\Filament\Resources\RepairRequests\RepairRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRepairRequest extends EditRecord
{
    protected static string $resource = RepairRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
