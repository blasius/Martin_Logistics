<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WialonService
{
    protected string $url;
    protected string $token;

    public function __construct()
    {
        $this->url = config('services.wialon.url', env('WIALON_API_URL'));
        $this->token = env('WIALON_API_TOKEN');
    }

    /**
     * Make a Wialon API call
     */
    public function call(string $svc, array $params = [])
    {
        $response = Http::get($this->url, [
            'svc'   => $svc,
            'params'=> json_encode($params),
            'sid'   => $this->token,
        ]);

        if ($response->failed()) {
            throw new \Exception("Wialon API error: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Test connection
     */
    public function test()
    {
        return $this->call('token/login', ['token' => $this->token]);
    }
}
