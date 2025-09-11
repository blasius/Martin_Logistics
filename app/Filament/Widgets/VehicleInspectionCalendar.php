<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\VehicleInspection;
use Illuminate\Support\Carbon;

class VehicleInspectionCalendar extends Widget
{
    protected string $view = 'filament.widgets.vehicle-inspection-calendar';

    // place the widget full width
    protected int|string|array $columnSpan = 'full';

    // Provide data to the blade view
    public function getViewData(): array
    {
        // choose month range you want to load (current month)
        $start = Carbon::now()->startOfMonth()->toDateString();
        $end = Carbon::now()->endOfMonth()->toDateString();

        $inspections = VehicleInspection::with('vehicle')
            ->whereBetween('scheduled_date', [$start, $end])
            ->get();

        $events = $inspections->map(function ($inspection) {
            // determine color
            if ($inspection->status === 'completed') {
                $color = 'green';
            } else {
                // pending or other
                if (Carbon::now()->greaterThan($inspection->scheduled_date)) {
                    $color = 'red'; // overdue
                } elseif ($inspection->scheduled_date->isSameMonth(Carbon::now())) {
                    $color = 'orange'; // due this month
                } else {
                    $color = 'green';
                }
            }

            // safe URL to the edit page (constructed; Filament resource route prefixes)
            $adminPath = config('filament.path', 'admin');
            $url = url("/{$adminPath}/resources/vehicle-inspections/{$inspection->id}/edit");

            return [
                'id'    => $inspection->id,
                'title' => $inspection->vehicle ? $inspection->vehicle->plate_number : 'Vehicle',
                'start' => $inspection->scheduled_date->toDateString(),
                'url'   => $url,
                'color' => $color,
                'allDay'=> true,
            ];
        })->toArray();

        return [
            'events' => $events,
        ];
    }
}
