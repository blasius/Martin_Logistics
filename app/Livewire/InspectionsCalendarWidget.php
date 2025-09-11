<?php

namespace App\Livewire;

use Guava\Calendar\Filament\CalendarWidget;
use App\Models\VehicleInspection;
use Illuminate\Database\Eloquent\Builder;
use Guava\Calendar\ValueObjects\FetchInfo;

class InspectionsCalendarWidget extends CalendarWidget
{
    /**
     * @return Builder
     */
    public function getEvents(FetchInfo $info): Builder
    {
        return VehicleInspection::query();
    }
}
