<?php

namespace App\Filament\Pages;

use App\Services\WialonService;
use Filament\Pages\Page;

class FleetOversight extends Page
{
    protected string $view = 'custom.fleet-oversight';
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-presentation-chart-line';

    public $vehicles = [];

    // This method is correctly fetching and preparing the data.
    public function mount(WialonService $wialonService): void
    {
        $this->vehicles = collect($wialonService->getUnitsWithPosition());
        Log:info("Vehicles: ".json_encode($this->vehicles));
    }

    // This method passes the data to the view.
    protected function getViewData(): array
    {
        return [
            'vehicles' => $this->vehicles,
            'utilization' => json_encode($this->vehicles),
        ];
    }
}
