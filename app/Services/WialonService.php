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
    private const MOVEMENT_DISTANCE_METERS = 50;
    private const SPEED_DELTA_KPH = 5;
    private const FUEL_DELTA_LITERS = 2;
    private const ODOMETER_DELTA_KM = 0.1;
    private const HEARTBEAT_INTERVAL_MINUTES = 60;

    public string $url;
    /** @var array Keyed array of API tokens from config */
    public array $tokens;
    /** @var array Storage for Session IDs (EIDs) keyed by token */
    public array $sids = [];
    protected RegionLocatorService $regionLocator;

    public function __construct(RegionLocatorService $regionLocator)
    {
        $this->url = config('services.wialon.url');

        // Load all available tokens into an array for multi-fleet iteration
        $this->tokens = array_filter([
            'fleet_1' => config('services.wialon.token'),
            'fleet_2' => config('services.wialon.token_fleet_2')
        ]);

        $this->regionLocator = $regionLocator;
    }

    /**
     * Diagnostic tool to verify all configured fleet connections.
     */
    public function test(): array
    {
        $report = [];
        foreach ($this->tokens as $key => $token) {
            try {
                $response = $this->callApi('token/login', ['token' => $token], $token);
                if (isset($response['eid'])) {
                    $report[$key] = [
                        'status' => '✅ Success',
                        'user'   => $response['user']['nm'] ?? 'Unknown',
                        'eid'    => $response['eid']
                    ];
                } else {
                    $report[$key] = ['status' => '❌ API Error', 'details' => $response];
                }
            } catch (\Exception $e) {
                $report[$key] = ['status' => '❌ Connection Failed', 'error' => $e->getMessage()];
            }
        }
        return $report;
    }

    /**
     * Authenticate a specific token and store its unique session ID.
     */
    protected function loginToken(string $token): string
    {
        $response = Http::get($this->url, [
            'svc' => 'token/login',
            'params' => json_encode(['token' => $token]),
        ]);

        $json = $response->json();

        if (!isset($json['eid'])) {
            throw new \Exception("Wialon login failed for token ending in ...".substr($token, -5));
        }

        $this->sids[$token] = $json['eid'];
        return $this->sids[$token];
    }

    /**
     * Retrieve or create a session ID for a specific token.
     */
    protected function ensureSession(string $token): string
    {
        return $this->sids[$token] ?? $this->loginToken($token);
    }

    /**
     * Executes an API call for a specific fleet account.
     */
    public function callApi(string $svc, array $params, string $token)
    {
        $sid = $this->ensureSession($token);

        $response = Http::get($this->url, [
            'svc'   => $svc,
            'sid'   => $sid,
            'params'=> json_encode($params),
        ]);

        $json = $response->json();

        // Handle expired session (Wialon Error 1)
        if (isset($json['error']) && $json['error'] === 1) {
            unset($this->sids[$token]);
            $sid = $this->loginToken($token);
            $response = Http::get($this->url, [
                'svc'   => $svc,
                'sid'   => $sid,
                'params'=> json_encode($params),
            ]);
            $json = $response->json();
        }

        return $json;
    }

    /**
     * Wrapper that executes the service call across ALL tokens
     * and merges 'items' results into a single collection.
     */
    public function call(string $svc, array $params = []): Collection
    {
        $allResults = collect();

        foreach ($this->tokens as $token) {
            $result = $this->callApi($svc, $params, $token);

            if (isset($result['items']) && is_array($result['items'])) {
                $allResults = $allResults->concat($result['items']);
            }
        }

        return $allResults;
    }

    /**
     * Fetches unit metadata and positions from all fleets.
     */
    public function getUnitsOnly(): array
    {
        $params = [
            'spec' => [
                'itemsType' => 'avl_unit',
                'propName' => 'sys_name',
                'propValueMask' => '*',
                'sortType' => 'sys_name',
            ],
            'force' => 1,
            'flags' => 1025, // Basic info + Position
            'from'  => 0,
            'to'    => 0,
        ];

        return $this->call('core/search_items', $params)
            ->filter(fn($unit) => isset($unit['id']))
            ->toArray();
    }

    /**
     * Syncs metadata to the database for all vehicles found in all accounts.
     */
    public function syncUnits(): void
    {
        Log::info('Wialon Multi-Fleet: Starting unit synchronization');
        $units = $this->getUnitsOnly();

        foreach ($units as $unit) {
            $pos = $unit['pos'] ?? null;
            $lmsg = $unit['lmsg'] ?? null;
            $params = $lmsg['p'] ?? [];

            WialonUnit::updateOrCreate(
                ['wialon_id' => $unit['id']],
                [
                    'name'            => $unit['nm'] ?? 'Unknown',
                    'last_lat'        => $pos['y'] ?? null,
                    'last_lon'        => $pos['x'] ?? null,
                    'speed'           => $pos['s'] ?? 0,
                    'ignition'        => (bool)($params['io_1'] ?? 0),
                    'gps_voltage'     => $params['io_25'] ?? null,
                    'vehicle_voltage' => $params['pwr_ext'] ?? null,
                    'last_update'     => isset($pos['t']) ? date('Y-m-d H:i:s', $pos['t']) : null,
                ]
            );
        }
    }

    /**
     * Processes live telemetry and fuel events for all fleets with stationary detection
     * and persistent 5-point windowed logic.
     */
    public function syncTelemetry(): int
    {
        $processedCount = 0;
        $now = now();

        // 1. Log the total number of configured fleets found after array_filter
        Log::info("Wialon Sync: Starting telemetry sync. Total fleets configured: " . count($this->tokens), [
            'configured_fleet_keys' => array_keys($this->tokens)
        ]);

        foreach ($this->tokens as $fleetKey => $token) {

            // 2. Log right before making the API call to see which fleet is acting
            Log::info("Wialon Sync: Fetching items for fleet [{$fleetKey}]", [
                'token_truncated' => '...' . substr($token, -5)
            ]);

            $result = $this->callApi('core/search_items', [
                'spec' => [
                    'itemsType' => 'avl_unit',
                    'propName' => 'sys_name',
                    'propValueMask' => '*',
                    'sortType' => 'sys_name',
                ],
                'force' => 1,
                'flags' => 5121,
                'from' => 0,
                'to' => 0,
            ], $token);

            // 3. Check if the API response is completely missing or malformed
            if (!isset($result['items'])) {
                Log::warning("Wialon Sync: No items block returned from API for fleet [{$fleetKey}]. Check token permissions.", [
                    'api_response' => $result
                ]);
                continue;
            }

            $incomingUnitsCount = count($result['items']);

            // 4. Log the volume of assets returned by this specific fleet
            Log::info("Wialon Sync: Fleet [{$fleetKey}] successfully returned {$incomingUnitsCount} units from API.");

            $unitIds = collect($result['items'])->pluck('id')->filter()->values();

            $wialonUnits = WialonUnit::whereIn('wialon_id', $unitIds)
                ->get()
                ->keyBy('wialon_id');

            $vehicleIds = $wialonUnits->pluck('vehicle_id')->filter()->values();

            $snapshots = DB::table('vehicle_snapshots')
                ->whereIn('vehicle_id', $vehicleIds)
                ->get()
                ->keyBy('vehicle_id');

            $fleetSkippedCount = 0;
            $fleetProcessedCount = 0;

            foreach ($result['items'] as $unitData) {
                $wialonUnit = $wialonUnits->get($unitData['id']);

                if (!$wialonUnit || !$wialonUnit->vehicle_id) {
                    // Log if an API asset isn't mapped in your DB yet
                    Log::debug("Wialon Sync: Skipping unit ID {$unitData['id']}. Reason: Not mapped to a local vehicle record.");
                    continue;
                }

                $vehicleId = $wialonUnit->vehicle_id;
                $pos = $unitData['pos'] ?? null;
                $params = $unitData['lmsg']['p'] ?? [];
                if (!$pos) continue;

                $rawFuelValue = $params['io_270'] ?? 0;
                $fuelLiters = $this->getCalibratedFuel($vehicleId, $rawFuelValue, $unitData['sens'] ?? []);

                $lat = $pos['y'];
                $lon = $pos['x'];
                $speed = $pos['s'] ?? 0;
                $odometer = ($unitData['cnm'] ?? 0) / 1000;
                $ignition = (bool)($params['io_239'] ?? 0);

                $recordedAt = isset($unitData['lmsg']['t'])
                    ? \Carbon\Carbon::createFromTimestamp($unitData['lmsg']['t'])
                    : $now;

                $previousSnapshot = $snapshots->get($vehicleId);

                // 5. Watch the Chronological Sanity Check bypass
                if (
                    $previousSnapshot &&
                    $previousSnapshot->last_seen_at &&
                    Carbon::parse($previousSnapshot->last_seen_at)->greaterThanOrEqualTo($recordedAt)
                ) {
                    $fleetSkippedCount++;
                    continue;
                }

                try {
                    $processed = DB::transaction(function () use ($vehicleId, $recordedAt, $lat, $lon, $speed, $fuelLiters, $ignition, $odometer, $now, $previousSnapshot) {

                        $currentSnapshot = DB::table('vehicle_snapshots')
                            ->where('vehicle_id', $vehicleId)
                            ->first();

                        if (
                            $currentSnapshot &&
                            $currentSnapshot->last_seen_at &&
                            Carbon::parse($currentSnapshot->last_seen_at)->greaterThanOrEqualTo($recordedAt)
                        ) {
                            return false;
                        }

                        $baselineSnapshot = $currentSnapshot ?? $previousSnapshot;

                        $shouldStorePoint = $this->shouldStoreTelemetryPoint(
                            $baselineSnapshot,
                            $vehicleId,
                            $recordedAt,
                            $lat,
                            $lon,
                            $speed,
                            $fuelLiters,
                            $ignition,
                            $odometer
                        );

                        if ($shouldStorePoint) {
                            DB::table('telemetry_points')->insertOrIgnore([
                                'vehicle_id' => $vehicleId,
                                'recorded_at' => $recordedAt,
                                'latitude' => $lat,
                                'longitude' => $lon,
                                'location' => DB::raw("ST_GeomFromText('POINT($lon $lat)', 4326)"),
                                'speed' => $speed,
                                'fuel_level_raw' => $fuelLiters,
                                'ignition' => $ignition,
                                'odometer' => $odometer,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);
                        }

                        $isLowFuel = ($fuelLiters >= 0 && $fuelLiters < 20);

                        DB::table('vehicle_snapshots')->updateOrInsert(
                            ['vehicle_id' => $vehicleId],
                            [
                                'last_seen_at' => $recordedAt,
                                'latitude' => $lat,
                                'longitude' => $lon,
                                'location' => DB::raw("ST_GeomFromText('POINT($lon $lat)', 4326)"),
                                'speed' => $speed,
                                'fuel_level' => $fuelLiters,
                                'ignition' => $ignition,
                                'is_moving' => $speed > 2,
                                'low_fuel' => $isLowFuel,
                                'updated_at' => $now,
                            ]
                        );

                        if ($baselineSnapshot && $fuelLiters >= 0 && $baselineSnapshot->fuel_level >= 0) {
                            $diff = $fuelLiters - $baselineSnapshot->fuel_level;
                            $refillThreshold = 10;
                            $drainThreshold = $ignition ? 12 : 5;

                            if ($diff >= $refillThreshold) {
                                $this->logEvent($vehicleId, 'fuel_refill', $diff, $recordedAt);
                            }
                            elseif ($diff <= ($drainThreshold * -1)) {
                                if ($this->isPersistentDrain($vehicleId, $fuelLiters, $drainThreshold)) {
                                    $this->logEvent($vehicleId, 'fuel_drain', abs($diff), $recordedAt);
                                }
                            }
                        }

                        return true;
                    });

                    if ($processed) {
                        $processedCount++;
                        $fleetProcessedCount++;
                    } else {
                        $fleetSkippedCount++;
                    }
                } catch (\Exception $e) {
                    Log::error("Wialon Sync: Exception processing vehicle [ID: {$vehicleId}] in fleet [{$fleetKey}]: " . $e->getMessage());
                }
            }

            // 6. Summary metrics for this specific fleet loop iteration
            Log::info("Wialon Sync: Completed iteration for fleet [{$fleetKey}]. Processed: {$fleetProcessedCount}, Skipped/Stale: {$fleetSkippedCount}");
        }

        Log::info("Wialon Sync: Global processing routine finished. Total mutated snapshots: {$processedCount}");
        return $processedCount;
    }

    /**
     * Accuracy Logic: Pulls calibration from DB or Updates from Wialon JSON if changed
     */
    private function getCalibratedFuel($vehicleId, $rawX, $sensors): float
    {
        if ($rawX <= 0) return -0.1;

        // Find the sensor of type "fuel level"
        $fuelSensor = collect($sensors)->firstWhere('t', 'fuel level');
        if (!$fuelSensor) return -0.1;

        $wialonMt = $fuelSensor['mt'] ?? 0;

        // Check if we have this cached locally
        $local = DB::table('fuel_calibrations')->where('vehicle_id', $vehicleId)->first();

        // If local is missing OR Wialon modification time is newer, update local table
        if (!$local || (isset($local->last_wialon_mt) && $local->last_wialon_mt < $wialonMt)) {
            $tableData = $fuelSensor['tbl'] ?? [];

            DB::table('fuel_calibrations')->updateOrInsert(
                ['vehicle_id' => $vehicleId],
                [
                    'calibration_table' => json_encode($tableData),
                    'last_wialon_mt' => $wialonMt, // Assumes you added this column
                    'updated_at' => now()
                ]
            );
            $calibrationTbl = $tableData;
        } else {
            $calibrationTbl = json_decode($local->calibration_table, true);
        }

        if (empty($calibrationTbl)) return -0.1;

        // Calculation Logic
        $row = $calibrationTbl[0];

        // CASE A: Linear Multiplier (Like your HOWO RAD 820Q)
        if (isset($row['a']) && isset($row['b'])) {
            $liters = ($rawX * $row['a']) + $row['b'];
            return (float) round(max(0, $liters), 2);
        }

        // CASE B: XY Table (Non-linear tanks)
        usort($calibrationTbl, fn($a, $b) => $a['x'] <=> $b['x']);
        if ($rawX <= $calibrationTbl[0]['x']) return (float)$calibrationTbl[0]['y'];

        for ($i = 0; $i < count($calibrationTbl) - 1; $i++) {
            $p0 = $calibrationTbl[$i];
            $p1 = $calibrationTbl[$i+1];
            if ($rawX >= $p0['x'] && $rawX <= $p1['x']) {
                $rangeX = $p1['x'] - $p0['x'];
                if ($rangeX == 0) return (float)$p0['y'];
                $val = $p0['y'] + ($rawX - $p0['x']) * ($p1['y'] - $p0['y']) / $rangeX;
                return (float) round($val, 2);
            }
        }

        return (float) round(end($calibrationTbl)['y'], 2);
    }

    private function shouldStoreTelemetryPoint(
        $previousSnapshot,
        int $vehicleId,
        Carbon $recordedAt,
        float $lat,
        float $lon,
        float $speed,
        float $fuelLiters,
        bool $ignition,
        float $odometer
    ): bool {
        if (!$previousSnapshot || $previousSnapshot->latitude === null || $previousSnapshot->longitude === null) {
            return true;
        }

        if ((bool) $previousSnapshot->ignition !== $ignition) {
            return true;
        }

        $wasMoving = ((float) $previousSnapshot->speed) > 2;
        $isMoving = $speed > 2;

        if ($wasMoving !== $isMoving || abs($speed - (float) $previousSnapshot->speed) >= self::SPEED_DELTA_KPH) {
            return true;
        }

        $previousFuel = (float) $previousSnapshot->fuel_level;

        if ($fuelLiters >= 0 && $previousFuel >= 0) {
            $isLowFuel = $fuelLiters < 20;

            if (
                abs($fuelLiters - $previousFuel) >= self::FUEL_DELTA_LITERS ||
                (bool) $previousSnapshot->low_fuel !== $isLowFuel
            ) {
                return true;
            }
        }

        $distanceMeters = $this->distanceMeters(
            (float) $previousSnapshot->latitude,
            (float) $previousSnapshot->longitude,
            $lat,
            $lon
        );

        if ($distanceMeters >= self::MOVEMENT_DISTANCE_METERS) {
            return true;
        }

        $lastStoredPoint = DB::table('telemetry_points')
            ->where('vehicle_id', $vehicleId)
            ->orderBy('recorded_at', 'desc')
            ->select('recorded_at', 'odometer')
            ->first();

        if (!$lastStoredPoint) {
            return true;
        }

        if (
            $lastStoredPoint->odometer !== null &&
            $odometer > 0 &&
            abs($odometer - (float) $lastStoredPoint->odometer) >= self::ODOMETER_DELTA_KM
        ) {
            return true;
        }

        return Carbon::parse($lastStoredPoint->recorded_at)
            ->lessThanOrEqualTo($recordedAt->copy()->subMinutes(self::HEARTBEAT_INTERVAL_MINUTES));
    }

    private function distanceMeters(float $fromLat, float $fromLon, float $toLat, float $toLon): float
    {
        $earthRadiusMeters = 6371000;

        $latDelta = deg2rad($toLat - $fromLat);
        $lonDelta = deg2rad($toLon - $fromLon);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($lonDelta / 2) ** 2;

        return $earthRadiusMeters * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function isPersistentDrain($vehicleId, $currentFuel, $threshold): bool
    {
        // Fetch last 5 points using the index on [vehicle_id, recorded_at]
        $recentPoints = DB::table('telemetry_points')
            ->where('vehicle_id', $vehicleId)
            ->orderBy('recorded_at', 'desc')
            ->limit(5)
            ->pluck('fuel_level_raw');

        if ($recentPoints->count() < 5) return false;

        // If any point in the last 5 was significantly higher,
        // it confirms the fuel level was previously stable/high.
        foreach ($recentPoints as $oldLevel) {
            if (($oldLevel - $currentFuel) >= $threshold) {
                return true; // Trend confirmed
            }
        }

        return false;
    }

    private function logEvent($vehicleId, $type, $value, $time)
    {
        DB::table('telemetry_events')->insert([
            'vehicle_id' => $vehicleId,
            'type' => $type,
            'value' => $value,
            'occurred_at' => $time,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Map helper for UI/Dashboard display.
     */
    public function getUnitsWithPosition(): Collection
    {
        return WialonUnit::all()->map(function ($unit) {
            return [
                'id'        => $unit->wialon_id,
                'name'      => $unit->name,
                'latitude'  => $unit->last_lat,
                'longitude' => $unit->last_lon,
                'speed'     => $unit->speed,
                'last_seen' => $unit->last_update,
                'region'    => $unit->last_lat ? $this->regionLocator->getRegionForPoint($unit->last_lat, $unit->last_lon) : 'N/A',
            ];
        })->filter(fn($u) => $u['latitude'] !== null);
    }
}
