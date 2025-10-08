<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\VehicleInsurance;
use Illuminate\Auth\Access\HandlesAuthorization;

class VehicleInsurancePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:VehicleInsurance');
    }

    public function view(AuthUser $authUser, VehicleInsurance $vehicleInsurance): bool
    {
        return $authUser->can('View:VehicleInsurance');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:VehicleInsurance');
    }

    public function update(AuthUser $authUser, VehicleInsurance $vehicleInsurance): bool
    {
        return $authUser->can('Update:VehicleInsurance');
    }

    public function delete(AuthUser $authUser, VehicleInsurance $vehicleInsurance): bool
    {
        return $authUser->can('Delete:VehicleInsurance');
    }

    public function restore(AuthUser $authUser, VehicleInsurance $vehicleInsurance): bool
    {
        return $authUser->can('Restore:VehicleInsurance');
    }

    public function forceDelete(AuthUser $authUser, VehicleInsurance $vehicleInsurance): bool
    {
        return $authUser->can('ForceDelete:VehicleInsurance');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:VehicleInsurance');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:VehicleInsurance');
    }

    public function replicate(AuthUser $authUser, VehicleInsurance $vehicleInsurance): bool
    {
        return $authUser->can('Replicate:VehicleInsurance');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:VehicleInsurance');
    }

}