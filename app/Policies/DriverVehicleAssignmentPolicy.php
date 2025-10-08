<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\DriverVehicleAssignment;
use Illuminate\Auth\Access\HandlesAuthorization;

class DriverVehicleAssignmentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DriverVehicleAssignment');
    }

    public function view(AuthUser $authUser, DriverVehicleAssignment $driverVehicleAssignment): bool
    {
        return $authUser->can('View:DriverVehicleAssignment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DriverVehicleAssignment');
    }

    public function update(AuthUser $authUser, DriverVehicleAssignment $driverVehicleAssignment): bool
    {
        return $authUser->can('Update:DriverVehicleAssignment');
    }

    public function delete(AuthUser $authUser, DriverVehicleAssignment $driverVehicleAssignment): bool
    {
        return $authUser->can('Delete:DriverVehicleAssignment');
    }

    public function restore(AuthUser $authUser, DriverVehicleAssignment $driverVehicleAssignment): bool
    {
        return $authUser->can('Restore:DriverVehicleAssignment');
    }

    public function forceDelete(AuthUser $authUser, DriverVehicleAssignment $driverVehicleAssignment): bool
    {
        return $authUser->can('ForceDelete:DriverVehicleAssignment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DriverVehicleAssignment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DriverVehicleAssignment');
    }

    public function replicate(AuthUser $authUser, DriverVehicleAssignment $driverVehicleAssignment): bool
    {
        return $authUser->can('Replicate:DriverVehicleAssignment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DriverVehicleAssignment');
    }

}