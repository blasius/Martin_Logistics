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
    /** @var array Keyed array of API tokens from config */
    protected array $tokens;
    /** @var array Storage for Session IDs (EIDs) keyed by token */
    protected array $sids = [];
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
    protected function callApi(string $svc, array $params, string $token)
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
     * Processes live telemetry and fuel events for all fleets.
     */
    public function syncTelemetry(): int
    {
        $processedCount = 0;

        foreach ($this->tokens as $fleetKey => $token) {
            Log::info("Wialon Sync: Starting processing for [$fleetKey]");

            try {
                // 1. Fetch items specifically for THIS token/fleet
                $result = $this->callApi('core/search_items', [
                    'spec' => [
                        'itemsType' => 'avl_unit',
                        'propName' => 'sys_name',
                        'propValueMask' => '*',
                        'sortType' => 'sys_name',
                    ],
                    'force' => 1,
                    'flags' => 1025,
                    'from' => 0,
                    'to' => 0,
                ], $token);

                if (!isset($result['items'])) {
                    Log::error("Wialon Sync: No items array returned for [$fleetKey]", ['response' => $result]);
                    continue;
                }

                $unitCount = count($result['items']);
                Log::info("Wialon Sync: Found $unitCount units in [$fleetKey]");

                foreach ($result['items'] as $unitData) {
                    // 2. Check if the unit exists in our local DB mapping
                    $wialonUnit = WialonUnit::where('wialon_id', $unitData['id'])->first();

                    if (!$wialonUnit) {
                        Log::warning("Wialon Sync: Unit ID {$unitData['id']} ({$unitData['nm']}) skipped. Reason: Not found in wialon_units table. Run syncUnits first.");
                        continue;
                    }

                    if (!$wialonUnit->vehicle_id) {
                        Log::warning("Wialon Sync: Unit {$unitData['nm']} skipped. Reason: No vehicle_id linked to this wialon_unit record.");
                        continue;
                    }

                    $vehicle = $wialonUnit->vehicle;
                    $pos = $unitData['pos'] ?? null;
                    $params = $unitData['lmsg']['p'] ?? [];

                    if (!$pos) {
                        Log::debug("Wialon Sync: Unit {$unitData['nm']} has no GPS position data.");
                        continue;
                    }

                    // 3. Process the data
                    $lat = $pos['y'];
                    $lon = $pos['x'];
                    $speed = $pos['s'] ?? 0;
                    $odometer = ($unitData['cnm'] ?? 0) / 1000;
                    $ignition = (bool)($params['io_239'] ?? 0);

                    // Your Fuel Logic
                    $rawFuel = $params['io_270'] ?? 0;
                    $currentFuel = ($rawFuel * 0.09770395701) - 0.09770395701;

                    $vehicle->update([
                        'last_lat' => $lat,
                        'last_lon' => $lon,
                        'current_fuel' => round($currentFuel, 2),
                        'current_odometer' => round($odometer, 2),
                        'is_engine_on' => $ignition,
                        'last_update' => now(),
                    ]);

                    // Significance & History
                    if ($this->evaluateSignificance($vehicle, $lat, $lon, $currentFuel, $odometer, $ignition)) {
                        DB::table('telemetry_recent')->insert([
                            'vehicle_id'  => $vehicle->id,
                            'fuel'        => round($currentFuel, 2),
                            'odometer'    => round($odometer, 2),
                            'speed'       => $speed,
                            'ignition'    => $ignition,
                            'location'    => DB::raw("ST_GeomFromText('POINT($lon $lat)', 4326)"),
                            'created_at'  => now(),
                        ]);
                    }

                    $processedCount++;
                }

            } catch (\Exception $e) {
                Log::error("Wialon Sync: Critical failure processing [$fleetKey]", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        Log::info("Wialon Sync: Finished. Total processed: $processedCount");
        return $processedCount;
    }

    protected function evaluateSignificance($vehicle, $lat, $lon, $fuel, $odo, $ignition): bool
    {
        $lastRecord = DB::table('telemetry_recent')
            ->where('vehicle_id', $vehicle->id)
            ->latest('id')
            ->first();

        if (!$lastRecord) return true;

        $hasMoved = (abs($lastRecord->odometer - $odo) > 0.05);
        $ignitionChanged = ($lastRecord->ignition != $ignition);
        $isStale = now()->diffInMinutes($lastRecord->created_at) >= 10;

        return $hasMoved || $ignitionChanged || $isStale;
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
