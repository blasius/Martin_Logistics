<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Driver;
use App\Models\DriverVehicleAssignment;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\TripStateMachineService;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

class MobileVehicleDocsController extends Controller
{
    public function __construct(protected TripStateMachineService $tripStateMachine) {}

    /**
     * Regulatory documents (insurance, inspection, plate, licence and any
     * uploaded vehicle documents) for the driver's currently assigned vehicle,
     * classified by expiry/maturity window.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->driver) {
            return response()->json(['message' => 'User is not registered as a driver.'], 403);
        }

        $windowDays = (int) $request->query('window_days', config('vehicle_docs.window_days', 30));
        $windowDays = max(1, min($windowDays, 365));

        $vehicle = $this->assignedVehicle($user);

        if (!$vehicle) {
            return response()->json([
                'message' => 'No vehicle is currently assigned to you.',
                'window_days' => $windowDays,
                'vehicle' => null,
                'summary' => $this->summarise([]),
                'documents' => [],
                'alerts' => [],
            ]);
        }

        $documents = $this->collectDocuments($vehicle, $user->driver, $windowDays);

        $alerts = collect($documents)
            ->filter(fn (array $doc) => in_array($doc['status'], ['expired', 'expiring_soon'], true))
            ->map(fn (array $doc) => $doc['alert'])
            ->filter()
            ->values()
            ->all();

        return response()->json([
            'message' => 'Vehicle regulatory documents.',
            'window_days' => $windowDays,
            'critical_days' => (int) config('vehicle_docs.critical_days', 7),
            'vehicle' => [
                'id' => $vehicle->id,
                'plate_number' => $vehicle->plate_number,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
            ],
            'summary' => $this->summarise($documents),
            'documents' => $documents,
            'alerts' => $alerts,
        ]);
    }

    /**
     * The vehicle on the driver's latest active trip, falling back to their
     * active vehicle assignment (vehicle-only portal dispatch leaves
     * trips.driver_id NULL, see MobileTripController).
     */
    private function assignedVehicle(User $user): ?Vehicle
    {
        $trip = Trip::with('vehicle')
            ->where('driver_id', $user->driver->id)
            ->whereNotIn('status', $this->tripStateMachine->terminalStatuses())
            ->latest()
            ->first();

        if ($trip?->vehicle) {
            return $trip->vehicle;
        }

        return DriverVehicleAssignment::with('vehicle')
            ->where('driver_id', $user->id)
            ->whereNull('end_date')
            ->latest('start_date')
            ->first()?->vehicle;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function collectDocuments(Vehicle $vehicle, Driver $driver, int $windowDays): array
    {
        $documents = [];

        $insurance = $vehicle->insurances()->orderByDesc('expiry_date')->first();
        if ($insurance) {
            $maturity = $this->maturity($insurance->expiry_date, $windowDays);
            $documents[] = array_merge([
                'type' => 'insurance',
                'label' => 'Insurance',
                'reference' => $insurance->policy_number,
                'provider' => $insurance->provider_name,
                'issued_on' => $this->dateString($insurance->issue_date),
                'expires_on' => $this->dateString($insurance->expiry_date),
                'file_url' => $this->fileUrl($insurance->document_path),
            ], $maturity, ['alert' => $this->alertFor('Insurance', $insurance->expiry_date, $maturity)]);
        }

        $inspection = $vehicle->inspections()->orderByDesc('scheduled_date')->first();
        if ($inspection) {
            $completed = (bool) $inspection->completed_date;
            $maturity = $this->maturity($inspection->scheduled_date, $windowDays, $completed);
            $documents[] = array_merge([
                'type' => 'inspection',
                'label' => 'Inspection',
                'reference' => $inspection->inspector_name,
                'scheduled_on' => $this->dateString($inspection->scheduled_date),
                'completed_on' => $this->dateString($inspection->completed_date),
                'is_completed' => $completed,
                'file_url' => $this->fileUrl($inspection->document_path),
            ], $maturity, ['alert' => $this->alertFor('Inspection', $inspection->scheduled_date, $maturity)]);
        }

        $documents[] = [
            'type' => 'plate',
            'label' => 'Plate number',
            'reference' => $vehicle->plate_number,
            'expires_on' => null,
            'status' => 'valid',
            'days_remaining' => null,
            'file_url' => null,
            'alert' => null,
        ];

        if ($driver->driving_licence || $driver->licence_expiry || $driver->licence_file) {
            $maturity = $this->maturity($driver->licence_expiry, $windowDays);
            $documents[] = array_merge([
                'type' => 'licence',
                'label' => 'Driving licence',
                'reference' => $driver->driving_licence,
                'expires_on' => $this->dateString($driver->licence_expiry),
                'file_url' => $this->fileUrl($driver->licence_file),
            ], $maturity, ['alert' => $this->alertFor('Driving licence', $driver->licence_expiry, $maturity)]);
        }

        $uploaded = Document::where('documentable_type', Vehicle::class)
            ->where('documentable_id', $vehicle->id)
            ->orderByDesc('expires_at')
            ->get();

        foreach ($uploaded as $document) {
            $maturity = $this->maturity($document->expires_at, $windowDays);
            $documents[] = array_merge([
                'type' => 'document',
                'label' => $document->name,
                'reference' => $document->category,
                'expires_on' => $this->dateString($document->expires_at),
                'file_url' => $document->url,
            ], $maturity, ['alert' => $this->alertFor($document->name, $document->expires_at, $maturity)]);
        }

        return $documents;
    }

    /**
     * Classify a document maturity date into a status and remaining days.
     * Accepts Carbon instances (cast columns) or plain date strings (uncast
     * legacy columns such as drivers.licence_expiry).
     *
     * @return array{status:string, days_remaining:?int}
     */
    private function maturity($date, int $windowDays, bool $completed = false): array
    {
        $date = $this->toCarbon($date);

        if (!$date) {
            return ['status' => 'unknown', 'days_remaining' => null];
        }

        if ($completed) {
            return ['status' => 'valid', 'days_remaining' => null];
        }

        $days = (int) round(now()->startOfDay()->diffInDays($date->startOfDay(), false));

        if ($days < 0) {
            return ['status' => 'expired', 'days_remaining' => $days];
        }

        if ($days <= $windowDays) {
            return ['status' => 'expiring_soon', 'days_remaining' => $days];
        }

        return ['status' => 'valid', 'days_remaining' => $days];
    }

    private function alertFor(string $label, $date, array $maturity): ?string
    {
        if (!$this->toCarbon($date)) {
            return null;
        }

        return match ($maturity['status']) {
            'expired' => sprintf(
                '%s expired %d day(s) ago',
                $label,
                abs((int) $maturity['days_remaining'])
            ),
            'expiring_soon' => sprintf(
                '%s expires in %d day(s)',
                $label,
                (int) $maturity['days_remaining']
            ),
            default => null,
        };
    }

    private function dateString($date): ?string
    {
        return $this->toCarbon($date)?->toDateString();
    }

    private function toCarbon($date): ?CarbonInterface
    {
        if (!$date) {
            return null;
        }

        if ($date instanceof CarbonInterface) {
            return $date;
        }

        try {
            return \Carbon\Carbon::parse($date);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $documents
     * @return array{expired:int, expiring_soon:int, valid:int, unknown:int, total:int}
     */
    private function summarise(array $documents): array
    {
        $counts = ['expired' => 0, 'expiring_soon' => 0, 'valid' => 0, 'unknown' => 0];

        foreach ($documents as $document) {
            $counts[$document['status']] = ($counts[$document['status']] ?? 0) + 1;
        }

        return $counts + ['total' => count($documents)];
    }

    private function fileUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/' . ltrim($path, '/'));
    }
}
