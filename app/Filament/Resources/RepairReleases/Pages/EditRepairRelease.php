<?php

namespace App\Filament\Resources\RepairReleases\Pages;

use App\Filament\Resources\RepairReleases\RepairReleaseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRepairRelease extends EditRecord
{
    protected static string $resource = RepairReleaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
