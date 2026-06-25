<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermissionAudit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleManagementController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')
            ->with('permissions', 'creator')
            ->orderBy('name')
            ->get();

        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'slug' => 'nullable|string|max:255|unique:roles,slug',
            'description' => 'nullable|string|max:1000',
            'is_super_admin' => 'boolean',
            'is_active' => 'boolean',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $data = [
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
            'guard_name' => 'web',
            'description' => $validated['description'] ?? null,
            'is_super_admin' => $validated['is_super_admin'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => $request->user()->id,
        ];

        $role = Role::create($data);

        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        RolePermissionAudit::create([
            'admin_id' => $request->user()->id,
            'action' => 'created_role',
            'target_type' => 'role',
            'target_id' => $role->id,
            'details' => ['name' => $role->name, 'permissions' => $validated['permissions'] ?? []],
        ]);

        return response()->json($role->load('permissions'), 201);
    }

    public function show(Role $role)
    {
        return response()->json($role->load('permissions', 'creator'));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'slug' => 'nullable|string|max:255|unique:roles,slug,' . $role->id,
            'description' => 'nullable|string|max:1000',
            'is_super_admin' => 'boolean',
            'is_active' => 'boolean',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $changes = [];

        if (isset($validated['name']) && $validated['name'] !== $role->name) {
            $changes['name'] = ['old' => $role->name, 'new' => $validated['name']];
        }

        $role->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
            'description' => $validated['description'] ?? $role->description,
            'is_super_admin' => $validated['is_super_admin'] ?? $role->is_super_admin,
            'is_active' => $validated['is_active'] ?? $role->is_active,
        ]);

        if (array_key_exists('permissions', $validated)) {
            $oldPerms = $role->permissions->pluck('name')->toArray();
            $role->syncPermissions($validated['permissions']);
            $changes['permissions'] = ['old' => $oldPerms, 'new' => $validated['permissions']];
        }

        RolePermissionAudit::create([
            'admin_id' => $request->user()->id,
            'action' => 'updated_role',
            'target_type' => 'role',
            'target_id' => $role->id,
            'details' => $changes,
        ]);

        return response()->json($role->fresh()->load('permissions'));
    }

    public function destroy(Request $request, Role $role)
    {
        if ($role->is_super_admin) {
            return response()->json(['message' => 'Cannot delete a super admin role.'], 422);
        }

        if ($role->users()->count() > 0) {
            return response()->json(['message' => 'Cannot delete a role that has assigned users.'], 422);
        }

        $roleName = $role->name;
        $role->delete();

        RolePermissionAudit::create([
            'admin_id' => $request->user()->id,
            'action' => 'deleted_role',
            'target_type' => 'role',
            'target_id' => $role->id,
            'details' => ['name' => $roleName],
        ]);

        return response()->json(['message' => 'Role deleted.']);
    }

    public function permissionsList()
    {
        return response()->json(Permission::orderBy('group')->orderBy('name')->pluck('name'));
    }

    public function permissionsGrouped()
    {
        $permissions = Permission::orderBy('name')
            ->get()
            ->groupBy(fn($p) => $p->group ?? 'general');

        return response()->json($permissions);
    }

    public function assignRoleToUser(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $user->syncRoles($validated['roles']);

        $user->roles()->updateExistingPivot(
            $user->roles->pluck('id')->toArray(),
            ['assigned_by' => $request->user()->id, 'assigned_at' => now()]
        );

        RolePermissionAudit::create([
            'admin_id' => $request->user()->id,
            'action' => 'assigned_roles',
            'target_type' => 'user',
            'target_id' => $user->id,
            'details' => ['roles' => $validated['roles']],
        ]);

        return response()->json($user->load('roles'));
    }

    public function auditLog()
    {
        return response()->json(
            RolePermissionAudit::with('admin')
                ->orderBy('created_at', 'desc')
                ->paginate(50)
        );
    }
}
