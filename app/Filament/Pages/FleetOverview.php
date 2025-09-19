<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Services\WialonService;
use Log;

class FleetOverview extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-truck';
    protected string $view = 'filament.pages.fleet-overview';
    protected static ?string $navigationLabel = 'Fleet Overview';
    protected static ?string $title = 'Fleet Overview';

    public $vehicles = [];

    public function mount(WialonService $wialonService): void
    {
        // Fetch vehicles from your service
        $this->vehicles = $wialonService->getUnitsWithPosition();
    }

    protected function getViewData(): array
    {
        return [
            'vehicles' => $this->vehicles,
        ];
    }
}
