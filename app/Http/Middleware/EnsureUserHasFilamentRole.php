<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasFilamentRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('filament.admin.auth.login');
        }

        $user = auth()->user();

        if ($user->hasAnyRole(['super_admin', 'admin', 'operator'])) {
            return $next($request);
        }

        abort(403, 'Unauthorized access.');
    }
}
