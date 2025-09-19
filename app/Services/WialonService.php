<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WialonService
{
    protected string $url;
    protected string $token;
    protected ?string $sid = null;

    public function __construct(RegionLocatorService $regionLocator)
    {
        $this->url = config('services.wialon.url', env('WIALON_API_URL'));
        $this->token = env('WIALON_API_TOKEN');
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

    public function getUnitsWithPosition(): Collection
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

        $result = $this->call('core/search_items', $params);

        if (isset($result['error'])) {
            throw new \Exception("Wialon error: " . json_encode($result));
        }

        $items = $result['items'] ?? [];

        return collect($items)->map(function ($unit) {
            $pos = $unit['pos'] ?? null;

            $lat = $pos['x'] ?? null;
            $lon = $pos['y'] ?? null;

            $region = $lat && $lon
                ? $this->regionLocator->getRegionForPoint($lon, $lat)
                : 'Unknown Region';

            return [
                'id'        => $unit['id'],
                'name'      => $unit['nm'] ?? '',
                'latitude'  => $lat,
                'longitude' => $lon,
                'altitude'  => $pos['z'] ?? null,
                'speed'     => $pos['s'] ?? null,
                'course'    => $pos['c'] ?? null,
                'last_seen' => isset($pos['t']) ? date('d-m-Y H:i:s', $pos['t']) : null,
                'region'    => $region,
            ];
        })->filter(fn($u) => $u['latitude'] && $u['longitude'])->values();
    }

}
