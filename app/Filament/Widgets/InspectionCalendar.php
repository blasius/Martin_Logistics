<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\VehicleInspection;
use Illuminate\Support\Carbon;

class InspectionCalendar extends Widget
{
    protected string $view = 'filament.widgets.inspection-calendar';

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $inspections = VehicleInspection::with('vehicle')
            ->whereBetween('scheduled_date', [$start, $end])
            ->get();

        $events = $inspections->map(function ($inspection) {
            $color = 'green';

            if ($inspection->status !== 'completed' && $inspection->scheduled_date < now()) {
                $color = 'red';
            } elseif ($inspection->status !== 'completed' && $inspection->scheduled_date->isCurrentMonth()) {
                $color = 'orange';
            }

            return [
                'title' => $inspection->vehicle->plate_number,
                'start' => Carbon::parse($inspection->scheduled_date)->toDateString(),
                'url'   => route('filament.admin.resources.vehicle-inspections.edit', $inspection),
                'color' => $color,
            ];
        });

        return [
            'events' => $events,
        ];
    }
}

