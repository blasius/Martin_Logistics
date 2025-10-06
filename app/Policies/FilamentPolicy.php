<?php

namespace App\Policies;

use App\Models\User;

class FilamentPolicy
{
    public static function authorize(User $user, string $permission): bool
    {
        return $user->can($permission) || $user->hasRole('Super Admin');
    }
}
