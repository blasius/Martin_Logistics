<?php

namespace App\Services;

use App\Models\MaintenanceSchedule;
use App\Models\RepairRequest;
use App\Models\RepairRequestItem;

class MaintenanceService
{
    public function checkDue(): array
    {
        $schedules = MaintenanceSchedule::with('vehicle')
            ->where('is_active', true)
            ->get();

        $due = [];

        foreach ($schedules as $schedule) {
            $reason = $this->isDue($schedule);

            if ($reason) {
                $due[] = [
                    'schedule' => $schedule,
                    'vehicle' => $schedule->vehicle,
                    'reason' => $reason,
                ];
            }
        }

        return $due;
    }

    public function isDue(MaintenanceSchedule $schedule): ?string
    {
        $vehicle = $schedule->vehicle;

        if ($schedule->interval_days && $schedule->last_done_at) {
            $nextDue = $schedule->last_done_at->addDays($schedule->interval_days);
            if (now()->greaterThanOrEqualTo($nextDue)) {
                return "Due by days — last done {$schedule->last_done_at->format('Y-m-d')}, interval {$schedule->interval_days} days";
            }
        }

        if ($schedule->interval_days && !$schedule->last_done_at) {
            return "Never done — interval {$schedule->interval_days} days";
        }

        if ($schedule->interval_km) {
            $currentKm = (float) ($vehicle->last_odometer ?? 0);
            $lastKm = (float) ($schedule->last_done_km ?? 0);
            $sinceLast = $currentKm - $lastKm;

            if ($sinceLast >= $schedule->interval_km) {
                return "Due by km — {$sinceLast} km since last service (interval {$schedule->interval_km} km)";
            }
        }

        return null;
    }

    public function generateRepairRequest(MaintenanceSchedule $schedule, ?int $userId = null): RepairRequest
    {
        $typeLabels = [
            'oil_change' => 'Oil Change',
            'tire_rotation' => 'Tire Rotation',
            'brake_check' => 'Brake Check',
            'inspection' => 'Inspection',
            'general' => 'General Maintenance',
        ];

        $label = $typeLabels[$schedule->type] ?? ucfirst($schedule->type);

        $request = RepairRequest::create([
            'vehicle_id' => $schedule->vehicle_id,
            'type' => 'maintenance',
            'priority' => 'medium',
            'description' => "Scheduled: {$label} — " . ($schedule->notes ?? ''),
            'status' => 'approved',
            'submitted_at' => now(),
        ]);

        RepairRequestItem::create([
            'repair_request_id' => $request->id,
            'description' => $label . ' — scheduled maintenance',
            'estimated_quantity' => 1,
            'estimated_unit_price' => 0,
        ]);

        $schedule->update([
            'last_done_at' => now(),
            'last_done_km' => $schedule->vehicle->last_odometer ?? $schedule->last_done_km,
        ]);

        return $request->fresh()->load('vehicle', 'items');
    }

    public function completeService(int $scheduleId, int $odometer): MaintenanceSchedule
    {
        $schedule = MaintenanceSchedule::findOrFail($scheduleId);

        $schedule->update([
            'last_done_at' => now(),
            'last_done_km' => $odometer,
        ]);

        return $schedule->fresh();
    }

    public function vehicleScheduleReport(int $vehicleId): array
    {
        $schedules = MaintenanceSchedule::with('vehicle')
            ->where('vehicle_id', $vehicleId)
            ->where('is_active', true)
            ->get();

        $result = [];
        foreach ($schedules as $schedule) {
            $dueReason = $this->isDue($schedule);
            $result[] = [
                'id' => $schedule->id,
                'type' => $schedule->type,
                'interval_km' => $schedule->interval_km,
                'interval_days' => $schedule->interval_days,
                'last_done_at' => $schedule->last_done_at,
                'last_done_km' => $schedule->last_done_km,
                'is_due' => $dueReason !== null,
                'due_reason' => $dueReason,
            ];
        }

        return $result;
    }
}
