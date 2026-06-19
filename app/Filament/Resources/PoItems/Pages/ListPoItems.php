<?php

namespace App\Filament\Resources\PoItems\Pages;

use App\Filament\Resources\PoItems\PoItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPoItems extends ListRecords
{
    protected static string $resource = PoItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
