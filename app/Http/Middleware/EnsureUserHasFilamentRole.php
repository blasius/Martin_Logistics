<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasFilamentRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Check if the user has one of the required roles
        /*if (!$user || !$user->hasAnyRole(['super_admin', 'admin', 'operator'])) {
            // If the user does not have the role, abort with a 403 error.
            abort(403, 'Unauthorized access.');
        }*/

        return $next($request);
    }
}
