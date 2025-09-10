<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogRedirects
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($response->isRedirect()) {
            Log::info('Redirect detected', [
                'from' => $request->fullUrl(),
                'to' => $response->headers->get('Location'),
            ]);
        }

        return $response;
    }
}
