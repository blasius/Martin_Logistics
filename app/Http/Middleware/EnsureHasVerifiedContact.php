<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasVerifiedContact
{
    public function handle($request, \Closure $next)
    {
        $user = $request->user();
        if (!$user || ! $user->hasVerifiedContact()) {
            return response()->json(['message' => 'Please verify at least one contact method.'], 403);
        }
        return $next($request);
    }

}
