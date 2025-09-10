<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // If user has 2FA enabled but has not verified in this session
        if ($user && $user->two_factor_secret && ! session('two_factor_passed')) {
            return redirect()->route('filament.2fa.challenge');
        }

        return $next($request);
    }
}
