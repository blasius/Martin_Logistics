<?php

namespace App\Livewire;

use Guava\Calendar\Filament\CalendarWidget;
use App\Models\VehicleInsurance;
use Illuminate\Database\Eloquent\Builder;
use Guava\Calendar\ValueObjects\FetchInfo;

class VehicleInsurancesCalendarWidget extends CalendarWidget
{
    /**
     * @return Builder
     */
    public function getEvents(FetchInfo $info): Builder
    {
        return VehicleInsurance::query()->with('vehicle');
    }
}
