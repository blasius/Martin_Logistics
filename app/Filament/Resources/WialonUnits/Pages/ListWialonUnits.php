<?php

namespace App\Filament\Resources\WialonUnits\Pages;

use App\Filament\Resources\WialonUnits\WialonUnitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWialonUnits extends ListRecords
{
    protected static string $resource = WialonUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
