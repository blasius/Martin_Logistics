<?php

namespace App\Services;

use App\Models\WialonUnit;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
}
