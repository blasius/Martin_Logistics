<?php

namespace App\Filament\Resources\WialonUnits\Pages;

use App\Filament\Resources\WialonUnits\WialonUnitResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWialonUnit extends EditRecord
{
    protected static string $resource = WialonUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
