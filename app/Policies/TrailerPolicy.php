<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Trailer;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrailerPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Trailer');
    }

    public function view(AuthUser $authUser, Trailer $trailer): bool
    {
        return $authUser->can('View:Trailer');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Trailer');
    }

    public function update(AuthUser $authUser, Trailer $trailer): bool
    {
        return $authUser->can('Update:Trailer');
    }

    public function delete(AuthUser $authUser, Trailer $trailer): bool
    {
        return $authUser->can('Delete:Trailer');
    }

    public function restore(AuthUser $authUser, Trailer $trailer): bool
    {
        return $authUser->can('Restore:Trailer');
    }

    public function forceDelete(AuthUser $authUser, Trailer $trailer): bool
    {
        return $authUser->can('ForceDelete:Trailer');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Trailer');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Trailer');
    }

    public function replicate(AuthUser $authUser, Trailer $trailer): bool
    {
        return $authUser->can('Replicate:Trailer');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Trailer');
    }

}