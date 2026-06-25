<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RolePermissionAudit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        return User::query()
            ->with('roles')
            ->when($request->search, function ($q, $s) {
                $q->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                      ->orWhere('email', 'like', "%{$s}%");
                });
            })
            ->when($request->role, fn($q, $r) => $q->role($r))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'roles' => 'sometimes|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        if (!empty($validated['roles'])) {
            $user->assignRole($validated['roles']);
            $user->roles()->updateExistingPivot(
                $user->roles->pluck('id')->toArray(),
                ['assigned_by' => $request->user()->id, 'assigned_at' => now()]
            );
        }

        $user->sendEmailVerificationNotification();

        RolePermissionAudit::create([
            'admin_id' => $request->user()->id,
            'action' => 'created_user',
            'target_type' => 'user',
            'target_id' => $user->id,
            'details' => ['name' => $user->name, 'roles' => $validated['roles'] ?? []],
        ]);

        return response()->json($user->load('roles'), 201);
    }

    public function show($id)
    {
        return User::with('roles.permissions')->findOrFail($id);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'sometimes|string|min:8',
            'roles' => 'sometimes|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        if (array_key_exists('roles', $validated)) {
            $oldRoles = $user->roles->pluck('name')->toArray();
            $user->syncRoles($validated['roles'] ?? []);
            $user->roles()->updateExistingPivot(
                $user->roles->pluck('id')->toArray(),
                ['assigned_by' => $request->user()->id, 'assigned_at' => now()]
            );

            RolePermissionAudit::create([
                'admin_id' => $request->user()->id,
                'action' => 'updated_user_roles',
                'target_type' => 'user',
                'target_id' => $user->id,
                'details' => ['old_roles' => $oldRoles, 'new_roles' => $validated['roles'] ?? []],
            ]);
        }

        return response()->json($user->fresh()->load('roles'));
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->hasRole('super_admin') && User::role('super_admin')->count() <= 1) {
            return response()->json(['message' => 'Cannot delete the last super admin.'], 422);
        }

        $user->delete();

        RolePermissionAudit::create([
            'admin_id' => $request->user()->id,
            'action' => 'deleted_user',
            'target_type' => 'user',
            'target_id' => $user->id,
            'details' => ['name' => $user->name],
        ]);

        return response()->json(['message' => 'User deleted.']);
    }

    public function rolesList()
    {
        return response()->json(Role::all()->pluck('name'));
    }
}
