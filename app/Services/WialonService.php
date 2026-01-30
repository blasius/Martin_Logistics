<?php

namespace App\Services;

use App\Models\WialonUnit;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WialonService
{
    protected string $url;
    protected string $token;
    protected ?string $sid = null;
    protected RegionLocatorService $regionLocator;

    public function __construct(RegionLocatorService $regionLocator)
    {
        $this->url = config('services.wialon.url');
        $this->token = config('services.wialon.token');
        $this->regionLocator = $regionLocator;
    }

    /**
     * Test connection
     */
    public function test()
    {
        return $this->call('token/login', ['token' => $this->token]);
    }

    public function getUnits()
    {
        return $this->call('core/search_items', [
            'spec' => [
                'itemsType' => 'avl_unit',
                'propName'  => 'sys_name',
                'propValueMask' => '*',
                'sortType' => 'sys_name'
            ],
            'force' => 1,
            'flags' => 1,
            'from'  => 0,
            'to'    => 200
        ]);
    }

    public function loginToken(): string
    {
        $response = Http::get($this->url, [
            'svc' => 'token/login',
            'params' => json_encode(['token' => $this->token]),
        ]);
        $json = $response->json();
        if (!isset($json['eid'])) {
            throw new \Exception("Wialon login failed: " . json_encode($json));
        }
        $this->sid = $json['eid'];
        return $this->sid;
    }

    protected function ensureSession(): string
    {
        if (!$this->sid) {
            return $this->loginToken();
        }
        return $this->sid;
    }

    public function call(string $svc, array $params = [])
    {
        $sid = $this->ensureSession();

        $response = Http::get($this->url, [
            'svc'   => $svc,
            'sid'   => $sid,
            'params'=> json_encode($params),
        ]);

        $json = $response->json();
        if (isset($json['error']) && $json['error'] === 1) {
            // session invalid; retry
            $this->sid = null;
            $sid = $this->loginToken();
            $response = Http::get($this->url, [
                'svc'   => $svc,
                'sid'   => $sid,
                'params'=> json_encode($params),
            ]);
            $json = $response->json();
        }
        return $json;
    }

    public function getUnitsOnly()
    {
        $params = [
            'spec' => [
                'itemsType' => 'avl_unit',
                'propName' => 'sys_name',
                'propValueMask' => '*',
                'sortType' => 'sys_name',
            ],
            'force' => 1,
            'flags' => 1025, // basic+pos
            'from'  => 0,
            'to'    => 0,
        ];

        $results = $this->call('core/search_items', $params);

        if (is_array($results) && isset($results['items'])) {
            // Filter the items array to keep only those with a numeric 'id' key.
            $validItems = array_filter($results['items'], function($item) {
                // The check for the 'id' key is sufficient. If 'id' is missing,
                // it's highly likely the other keys are as well.
                return isset($item['id']) && is_numeric($item['id']);
            });

            // Return the cleaned array.
            return $validItems;
        }

        // Return an empty array if the expected data structure is not found.
        return [];
    }

    public function processVehicleTelemetry($vehicle, $newData)
    {
        $lastFuel = $vehicle->current_fuel;
        $newFuel = $newData['fuel'];
        $fuelDiff = $newFuel - $lastFuel;

        $eventType = null;
        $eventValue = 0;

        // 1. Detect Refill (Threshold: +10L)
        if ($fuelDiff >= 10) {
            $eventType = 'refill';
            $eventValue = $fuelDiff;
        }
        // 2. Detect Theft (Threshold: -5L while engine is off)
        elseif ($fuelDiff <= -5 && $newData['ignition'] == 0) {
            $eventType = 'theft';
            $eventValue = abs($fuelDiff);
        }

        // 3. Update the "Live" Vehicle State
        $vehicle->update([
            'current_fuel' => $newFuel,
            'current_odometer' => $newData['milage'],
            'last_lat' => $newData['lat'],
            'last_lon' => $newData['lon'],
            // Update stationary timestamp if speed is 0
            'stationary_at' => ($newData['speed'] > 0) ? null : ($vehicle->stationary_at ?? now()),
        ]);

        // 4. Deciding whether to write a "History Point" to telemetry_recent
        // We save if an event happened OR if it's been more than 5 mins since the last log
        $lastLog = DB::table('telemetry_recent')
            ->where('vehicle_id', $vehicle->id)
            ->latest('created_at')
            ->first();

        $isTimeLog = !$lastLog || Carbon::parse($lastLog->created_at)->diffInMinutes(now()) >= 5;

        if ($eventType || $isTimeLog) {
            DB::table('telemetry_recent')->insert([
                'vehicle_id' => $vehicle->id,
                'fuel' => $newFuel,
                'speed' => $newData['speed'],
                'location' => DB::raw("ST_GeomFromText('POINT({$newData['lon']} {$newData['lat']})')"),
                'event_type' => $eventType, // 'theft', 'refill', or null
                'event_value' => $eventValue,
                'created_at' => now(),
            ]);
        }
    }

    public function getUnitsWithPosition(): Collection
    {
        // Get the data from the local database
        $vehicles = WialonUnit::all();
        //Log::info('The vehicles: ' . $vehicles->count());
        //Log::info('The vehicles: ' . json_encode($vehicles));

        return $vehicles->map(function ($unit) {
            $lat = $unit->last_lat;
            $lon = $unit->last_lon;

            $region = $lat && $lon
                ? $this->regionLocator->getRegionForPoint($lat, $lon)
                : 'Unknown Region';
            Log::info('Region: ' . $region);

            return [
                'id'        => $unit->wialon_id,
                'name'      => $unit->name,
                'latitude'  => $lat,
                'longitude' => $lon,
                'speed'     => $unit->speed,
                'last_seen' => $unit->last_update,
                'region'    => $region,
            ];
        })->filter(fn($u) => $u['latitude'] && $u['longitude'])->values();
    }

    public function syncUnits(): void
    {
        Log::info('Passage');
        $units = $this->getUnitsOnly();

        foreach ($units as $unit) {
            Log::info("Unit: " . json_encode($unit));
            $pos = $unit['pos'] ?? null;
            $lmsg = $unit['lmsg'] ?? null;
            $power = $lmsg['p'] ?? null;

            // Correcting the variable assignment for latitude and longitude
            $lat = $pos['y'] ?? null;
            $lon = $pos['x'] ?? null;

            // Safely retrieve the last update time
            $last_update = isset($pos['t']) ? date('Y-m-d H:i:s', $pos['t']) : null;

            WialonUnit::updateOrCreate(
                ['wialon_id' => $unit['id']],
                [
                    'vehicle_id'      => null,
                    'name'            => $unit['nm'] ?? 'Unknown',
                    'uid'             => null,
                    'device_type'     => null,
                    'last_lat'        => $lat,
                    'last_lon'        => $lon,
                    // CORRECTED: Access speed from the 'pos' array
                    'speed'           => $pos['s'] ?? null,
                    // CORRECTED: Safely access ignition status
                    'ignition'        => $power['io_1'] ?? null,
                    // CORRECTED: Safely access GPS voltage
                    'gps_voltage'     => $power['io_25'] ?? null,
                    // CORRECTED: Safely access vehicle voltage
                    'vehicle_voltage' => $power['pwr_ext'] ?? null,
                    'last_update'     => $last_update,
                    'is_linked'       => 0,
                ]
            );
        }
    }

    /**
     * Main entry point for the Scheduled Job.
     * Fetches all units from Wialon and processes their telemetry.
     */
    public function syncTelemetry()
    {
        $units = $this->getUnitsOnly();

        if (empty($units)) {
            Log::info("Wialon Sync: No units found to process.");
            return 0;
        }

        $processedCount = 0;

        foreach ($units as $unitData) {
            $wialonUnit = WialonUnit::where('wialon_id', $unitData['id'])->first();
            if (!$wialonUnit || !$wialonUnit->vehicle_id) continue;

            $vehicle = $wialonUnit->vehicle;
            $pos = $unitData['pos'] ?? null;
            $params = $unitData['lmsg']['p'] ?? [];
            if (!$pos) continue;

            // Parse Data
            $lat = $pos['y'];
            $lon = $pos['x'];
            $speed = $pos['s'] ?? 0;
            $odometer = ($unitData['cnm'] ?? 0) / 1000;
            $ignition = (bool)($params['io_239'] ?? 0);
            $rawFuel = $params['io_270'] ?? 0;
            $currentFuel = ($rawFuel * 0.09770395701) - 0.09770395701;

            // --- NEW: Event Detection Logic ---
            $eventType = null;
            $eventValue = 0;
            $fuelDiff = $currentFuel - $vehicle->current_fuel;

            // 1. Refill Detection (Threshold: +10L)
            if ($fuelDiff >= 10) {
                $eventType = 'refill';
                $eventValue = $fuelDiff;
            }
            // 2. Theft Detection (Threshold: -5L loss while ignition is OFF)
            elseif ($fuelDiff <= -5 && !$ignition) {
                $eventType = 'theft';
                $eventValue = abs($fuelDiff);
            }

            // 3. Stationary Logic (For the "24h+ Stops" tile)
            $stationaryAt = $vehicle->stationary_at;
            if ($speed > 0) {
                $stationaryAt = null; // Vehicle is moving
            } elseif ($speed == 0 && is_null($stationaryAt)) {
                $stationaryAt = now(); // Just stopped
            }

            // --- Update the Vehicle (Live Dashboard State) ---
            $vehicle->update([
                'last_lat' => $lat,
                'last_lon' => $lon,
                'current_fuel' => round($currentFuel, 2),
                'current_odometer' => round($odometer, 2),
                'is_engine_on' => $ignition,
                'stationary_at' => $stationaryAt, // Critical for "Operations" tile
                'last_update' => now(),
            ]);

            // --- Determine if we save to telemetry_recent ---
            // Record if: Significance filter passes OR a Fuel Event was detected
            $isSignificant = $this->evaluateSignificance($vehicle, $lat, $lon, $currentFuel, $odometer, $ignition);

            if ($isSignificant || $eventType) {
                DB::table('telemetry_recent')->insert([
                    'vehicle_id'  => $vehicle->id,
                    'fuel'        => round($currentFuel, 2),
                    'odometer'    => round($odometer, 2),
                    'speed'       => $speed,
                    'ignition'    => $ignition,
                    'event_type'  => $eventType,   // 'theft', 'refill', or null
                    'event_value' => round($eventValue, 2),
                    'location'    => DB::raw("ST_GeomFromText('POINT($lon $lat)', 4326)"),
                    'created_at'  => now(),
                ]);
            }

            $processedCount++;
        }

        return $processedCount;
    }

    private function evaluateSignificance($vehicle, $lat, $lon, $fuel, $odo, $ignition): bool
    {
        $lastRecord = DB::table('telemetry_recent')
            ->where('vehicle_id', $vehicle->id)
            ->latest('id')
            ->first();

        if (!$lastRecord) return true;

        // 1. Movement: Has it moved more than 50 meters?
        $hasMoved = (abs($lastRecord->odometer - $odo) > 0.05);

        // 2. Engine State: Did someone turn the truck on/off?
        $ignitionChanged = ($lastRecord->ignition != $ignition);

        // 3. Heartbeat: If nothing happened, save a point every 10 mins anyway
        $isStale = now()->diffInMinutes($lastRecord->created_at) >= 10;

        return $hasMoved || $ignitionChanged || $isStale;
    }
}
