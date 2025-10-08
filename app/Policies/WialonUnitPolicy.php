<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\WialonUnit;
use Illuminate\Auth\Access\HandlesAuthorization;

class WialonUnitPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:WialonUnit');
    }

    public function view(AuthUser $authUser, WialonUnit $wialonUnit): bool
    {
        return $authUser->can('View:WialonUnit');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:WialonUnit');
    }

    public function update(AuthUser $authUser, WialonUnit $wialonUnit): bool
    {
        return $authUser->can('Update:WialonUnit');
    }

    public function delete(AuthUser $authUser, WialonUnit $wialonUnit): bool
    {
        return $authUser->can('Delete:WialonUnit');
    }

    public function restore(AuthUser $authUser, WialonUnit $wialonUnit): bool
    {
        return $authUser->can('Restore:WialonUnit');
    }

    public function forceDelete(AuthUser $authUser, WialonUnit $wialonUnit): bool
    {
        return $authUser->can('ForceDelete:WialonUnit');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:WialonUnit');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:WialonUnit');
    }

    public function replicate(AuthUser $authUser, WialonUnit $wialonUnit): bool
    {
        return $authUser->can('Replicate:WialonUnit');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:WialonUnit');
    }

}