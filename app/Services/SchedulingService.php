<?php

namespace App\Services;

use App\Models\TimeSlot;
use App\Models\Trip;
use App\Models\Order;
use App\Models\MaintenanceSchedule;
use App\Models\YardDockDoorAssignment;
use Illuminate\Support\Carbon;

class SchedulingService
{
    public function getEvents(Carbon $start, Carbon $end): array
    {
        $events = [];

        $timeSlots = TimeSlot::where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->with(['vehicle:id,plate_number', 'driver:id,name'])
            ->get();

        foreach ($timeSlots as $slot) {
            $events[] = [
                'id' => 'slot-' . $slot->id,
                'title' => $slot->title,
                'start' => $slot->start_time->toIso8601String(),
                'end' => $slot->end_time->toIso8601String(),
                'type' => $slot->type,
                'status' => $slot->status,
                'color' => $slot->color ?? $this->typeColor($slot->type),
                'extendedProps' => [
                    'vehicle' => $slot->vehicle?->plate_number,
                    'driver' => $slot->driver?->name,
                    'notes' => $slot->notes,
                ],
            ];
        }

        $trips = Trip::where('departure_time', '<', $end)
            ->where(function ($q) use ($start) {
                $q->whereNull('arrival_time')
                    ->orWhere('arrival_time', '>', $start);
            })
            ->with(['vehicle:id,plate_number', 'driver:id,name', 'route:id,name'])
            ->get();

        foreach ($trips as $trip) {
            if (!$trip->departure_time) continue;
            $events[] = [
                'id' => 'trip-' . $trip->id,
                'title' => 'Trip: ' . ($trip->route?->name ?? 'N/A'),
                'start' => $trip->departure_time->toIso8601String(),
                'end' => ($trip->arrival_time ?? $trip->departure_time->addHours(8))->toIso8601String(),
                'type' => 'trip',
                'status' => $trip->status,
                'color' => '#3b82f6',
                'extendedProps' => [
                    'vehicle' => $trip->vehicle?->plate_number,
                    'driver' => $trip->driver?->name,
                    'status' => $trip->status,
                ],
            ];
        }

        $maintenance = MaintenanceSchedule::where('scheduled_date', '>=', $start->toDateString())
            ->where('scheduled_date', '<=', $end->toDateString())
            ->with(['vehicle:id,plate_number'])
            ->get();

        foreach ($maintenance as $m) {
            $dayStart = Carbon::parse($m->scheduled_date)->startOfDay();
            $events[] = [
                'id' => 'maint-' . $m->id,
                'title' => 'Maintenance: ' . ($m->type ?? 'Service'),
                'start' => $dayStart->toIso8601String(),
                'end' => $dayStart->addDay()->toIso8601String(),
                'type' => 'maintenance',
                'status' => $m->status,
                'color' => '#f59e0b',
                'extendedProps' => [
                    'vehicle' => $m->vehicle?->plate_number,
                    'notes' => $m->description,
                ],
            ];
        }

        $orders = Order::where('pickup_date', '>=', $start->toDateString())
            ->where('pickup_date', '<=', $end->toDateString())
            ->with(['client:id,name'])
            ->get();

        foreach ($orders as $order) {
            if (!$order->pickup_date) continue;
            $dayStart = Carbon::parse($order->pickup_date)->startOfDay();
            $events[] = [
                'id' => 'order-' . $order->id,
                'title' => 'Pickup: ' . ($order->reference ?? '#' . $order->id),
                'start' => $dayStart->toIso8601String(),
                'end' => $dayStart->addDay()->toIso8601String(),
                'type' => 'pickup',
                'status' => $order->status,
                'color' => '#10b981',
                'extendedProps' => [
                    'client' => $order->client?->name,
                    'reference' => $order->reference,
                ],
            ];
        }

        return $events;
    }

    public function checkConflict(int $vehicleId, Carbon $start, Carbon $end, ?int $excludeSlotId = null): array
    {
        $query = TimeSlot::where('vehicle_id', $vehicleId)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->whereIn('status', ['scheduled', 'confirmed', 'in_progress']);

        if ($excludeSlotId) {
            $query->where('id', '!=', $excludeSlotId);
        }

        return $query->get()->toArray();
    }

    private function typeColor(string $type): string
    {
        return match ($type) {
            'pickup', 'delivery' => '#10b981',
            'driver_shift' => '#8b5cf6',
            'maintenance' => '#f59e0b',
            'dock_reservation' => '#06b6d4',
            'trip' => '#3b82f6',
            default => '#6b7280',
        };
    }
}
