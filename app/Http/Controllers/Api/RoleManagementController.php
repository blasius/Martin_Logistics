<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleManagementController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')
            ->with('permissions')
            ->orderBy('name')
            ->get();

        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::create(['name' => $validated['name']]);

        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return response()->json($role->load('permissions'), 201);
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'sometimes|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role->update(['name' => $validated['name']]);

        if (array_key_exists('permissions', $validated)) {
            $role->syncPermissions($validated['permissions']);
        }

        return response()->json($role->fresh()->load('permissions'));
    }

    public function destroy(Request $request, Role $role)
    {
        if ($role->name === 'super_admin') {
            return response()->json(['message' => 'Cannot delete the super_admin role.'], 422);
        }

        if ($role->users()->count() > 0) {
            return response()->json(['message' => 'Cannot delete a role that has assigned users.'], 422);
        }

        $role->delete();

        return response()->json(['message' => 'Role deleted.']);
    }

    public function permissionsList()
    {
        return response()->json(Permission::orderBy('name')->pluck('name'));
    }
}
