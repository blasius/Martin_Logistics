<?php

namespace App\Filament\Resources\RepairReleases\Pages;

use App\Filament\Resources\RepairReleases\RepairReleaseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRepairReleases extends ListRecords
{
    protected static string $resource = RepairReleaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
