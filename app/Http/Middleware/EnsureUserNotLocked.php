<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserNotLocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->locked_at !== null) {
            // Drop any session-backed authentication for this request. Token-based
            // API requests have no session, so this is skipped for them.
            if ($request->hasSession()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your account has been deactivated. Please contact an administrator.',
                ], 403);
            }

            return redirect()->route('login')
                ->withErrors(['email' => 'Your account has been deactivated. Please contact an administrator.']);
        }

        return $next($request);
    }
}
