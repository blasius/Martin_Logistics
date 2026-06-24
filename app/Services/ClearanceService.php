<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\MaintenanceSchedule;
use App\Models\RepairRequest;
use App\Models\TrafficFine;
use App\Models\Vehicle;
use App\Models\VehicleInspection;
use App\Models\VehicleInsurance;

class ClearanceService
{
    public function check(int $vehicleId, ?int $driverId = null): array
    {
        $vehicle = Vehicle::with(['insurances', 'inspections', 'repairRequests', 'trafficFines'])->find($vehicleId);
        if (!$vehicle) {
            return [];
        }

        $checks = [];

        // 1. Vehicle status
        $checks[] = $this->checkVehicleStatus($vehicle);

        // 2. Open repair requests
        $checks[] = $this->checkOpenRepairs($vehicle);

        // 3. PM overdue
        $checks[] = $this->checkPmDue($vehicle);

        // 4. Insurance expired
        $checks[] = $this->checkInsurance($vehicle);

        // 5. Inspection overdue
        $checks[] = $this->checkInspection($vehicle);

        // 6. Traffic fines pending
        $checks[] = $this->checkTrafficFines($vehicle);

        // 7. Driver license
        if ($driverId) {
            $checks[] = $this->checkDriverLicense($driverId);
        }

        return $checks;
    }

    public function isClear(array $checks): bool
    {
        return collect($checks)->every(fn($c) => $c['passed']);
    }

    public function blockingChecks(array $checks): array
    {
        return array_values(array_filter($checks, fn($c) => !$c['passed'] && $c['severity'] === 'blocking'));
    }

    private function checkVehicleStatus(Vehicle $vehicle): array
    {
        $blocking = in_array($vehicle->status, ['maintenance', 'inactive']);
        return [
            'check' => 'vehicle_status',
            'label' => 'Vehicle Status',
            'passed' => !$blocking,
            'severity' => 'blocking',
            'message' => $blocking
                ? "Vehicle status is '{$vehicle->status}'"
                : 'Vehicle is active and operational',
            'metadata' => ['status' => $vehicle->status],
        ];
    }

    private function checkOpenRepairs(Vehicle $vehicle): array
    {
        $openRepairs = $vehicle->repairRequests()
            ->whereNotIn('status', ['released', 'cancelled'])
            ->exists();
        return [
            'check' => 'open_repairs',
            'label' => 'Open Repair Requests',
            'passed' => !$openRepairs,
            'severity' => 'blocking',
            'message' => $openRepairs
                ? 'Vehicle has unresolved repair requests'
                : 'No open repair requests',
            'metadata' => [],
        ];
    }

    private function checkPmDue(Vehicle $vehicle): array
    {
        $schedules = MaintenanceSchedule::where('vehicle_id', $vehicle->id)
            ->where('is_active', true)
            ->get();

        $dueSchedule = null;
        foreach ($schedules as $schedule) {
            if ((new MaintenanceService)->isDue($schedule)) {
                $dueSchedule = $schedule;
                break;
            }
        }

        return [
            'check' => 'pm_due',
            'label' => 'Preventive Maintenance',
            'passed' => !$dueSchedule,
            'severity' => 'blocking',
            'message' => $dueSchedule
                ? "PM overdue: {$dueSchedule->type}"
                : 'All PM schedules up to date',
            'metadata' => $dueSchedule ? ['type' => $dueSchedule->type] : [],
        ];
    }

    private function checkInsurance(Vehicle $vehicle): array
    {
        $hasActive = $vehicle->insurances()
            ->where('expiry_date', '>=', now())
            ->exists();
        return [
            'check' => 'insurance',
            'label' => 'Vehicle Insurance',
            'passed' => $hasActive,
            'severity' => 'blocking',
            'message' => $hasActive
                ? 'Insurance is active'
                : 'No active insurance policy',
            'metadata' => [],
        ];
    }

    private function checkInspection(Vehicle $vehicle): array
    {
        $latest = $vehicle->inspections()->latest('scheduled_date')->first();
        $hasValid = $latest && $latest->completed_date && $latest->completed_date->gte(now()->subMonths(6));
        return [
            'check' => 'inspection',
            'label' => 'Vehicle Inspection',
            'passed' => (bool) $hasValid,
            'severity' => 'blocking',
            'message' => $hasValid
                ? 'Inspection passed on ' . $latest->completed_date->format('Y-m-d')
                : ($latest ? 'No recent completed inspection' : 'No inspection record found'),
            'metadata' => [],
        ];
    }

    private function checkTrafficFines(Vehicle $vehicle): array
    {
        $hasUnpaid = $vehicle->trafficFines()
            ->where('status', 'PENDING')
            ->exists();
        return [
            'check' => 'traffic_fines',
            'label' => 'Traffic Fines',
            'passed' => !$hasUnpaid,
            'severity' => 'blocking',
            'message' => $hasUnpaid
                ? 'Vehicle has unpaid traffic fines'
                : 'No unpaid fines',
            'metadata' => [],
        ];
    }

    private function checkDriverLicense(int $driverId): array
    {
        $driver = Driver::find($driverId);
        if (!$driver || !$driver->licence_expiry) {
            return [
                'check' => 'driver_license',
                'label' => 'Driver License',
                'passed' => false,
                'severity' => 'blocking',
                'message' => 'Driver license not on record',
                'metadata' => [],
            ];
        }
        $expired = $driver->licence_expiry->isPast();
        return [
            'check' => 'driver_license',
            'label' => 'Driver License',
            'passed' => !$expired,
            'severity' => 'blocking',
            'message' => $expired
                ? 'License expired on ' . $driver->licence_expiry->format('Y-m-d')
                : 'License valid until ' . $driver->licence_expiry->format('Y-m-d'),
            'metadata' => ['expiry' => $driver->licence_expiry->format('Y-m-d')],
        ];
    }
}
