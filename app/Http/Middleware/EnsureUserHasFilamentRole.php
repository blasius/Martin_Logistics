<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasFilamentRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyRole(['super_admin', 'Admin', 'Operator'])) {
            abort(403, 'You are not authorized to access the admin panel.');
        }

        return $next($request);
    }
}
