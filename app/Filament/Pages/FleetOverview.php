<?php

namespace App\Filament\Pages;

use App\Models\WialonUnit;
use Filament\Pages\Page;
use App\Services\WialonService;

class FleetOverview extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-truck';
    protected string $view = 'filament.pages.fleet-overview';
    protected static ?string $navigationLabel = 'Fleet Overview';
    protected static ?string $title = 'Fleet Overview';

    public $vehicles = [];

    // This method is correctly fetching and preparing the data.
    public function mount(WialonService $wialonService): void
    {
        $this->vehicles = collect($wialonService->getUnitsWithPosition());
    }

    // This method passes the data to the view.
    protected function getViewData(): array
    {
        return [
            'vehicles' => $this->vehicles,
        ];
    }
}
