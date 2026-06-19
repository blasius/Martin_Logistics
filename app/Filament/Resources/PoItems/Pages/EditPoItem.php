<?php

namespace App\Filament\Resources\PoItems\Pages;

use App\Filament\Resources\PoItems\PoItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPoItem extends EditRecord
{
    protected static string $resource = PoItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
