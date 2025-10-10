<?php

namespace app\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // Define your model-to-policy mappings here if needed
    ];
    public function boot(): void {
        $this->registerPolicies();

        // ✅ Super admin override — this user bypasses all permission checks
        Gate::before(function ($user, $ability) {
            // You can check by email or role
            return $user->hasRole('super_admin') ? true : null;
        });
    }
}
