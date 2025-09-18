<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WialonService
{
    protected string $url;
    protected string $token;
    protected ?string $sid = null;

    public function __construct()
    {
        $this->url = config('services.wialon.url', env('WIALON_API_URL'));
        $this->token = env('WIALON_API_TOKEN');
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

}
