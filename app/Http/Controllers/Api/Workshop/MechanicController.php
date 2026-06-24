<?php

namespace App\Http\Controllers\Api\Workshop;

use App\Http\Controllers\Controller;
use App\Models\MechanicProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class MechanicController extends Controller
{
    public function index()
    {
        $roleExists = Role::where('name', 'mechanic')->where('guard_name', 'web')->exists();

        if (!$roleExists) {
            return response()->json([
                'mechanics' => [],
                'role_missing' => true,
            ]);
        }

        return response()->json([
            'mechanics' => User::role('mechanic')
                ->with('mechanicProfile')
                ->get(['id', 'name', 'email']),
            'role_missing' => false,
        ]);
    }

    public function store(Request $request)
    {
        $roleExists = Role::where('name', 'mechanic')->where('guard_name', 'web')->exists();

        if (!$roleExists) {
            return response()->json([
                'message' => 'The "mechanic" role does not exist. Please create it first in the Roles management page.',
                'role_missing' => true,
            ], 422);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'specialization' => 'nullable|string|max:100',
            'hourly_rate' => 'nullable|numeric|min:0',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $user->assignRole('mechanic');

        $user->mechanicProfile()->create([
            'specialization' => $validated['specialization'] ?? null,
            'hourly_rate' => $validated['hourly_rate'] ?? null,
        ]);

        return $user->load('mechanicProfile')->only(['id', 'name', 'email', 'mechanicProfile']);
    }

    public function update(Request $request, User $user)
    {
        if (!$user->hasRole('mechanic')) {
            abort(422, 'User is not a mechanic.');
        }

        $validated = $request->validate([
            'specialization' => 'nullable|string|max:100',
            'hourly_rate' => 'nullable|numeric|min:0',
        ]);

        $profile = $user->mechanicProfile ?: $user->mechanicProfile()->create([]);
        $profile->update($validated);

        return $user->load('mechanicProfile')->only(['id', 'name', 'email', 'mechanicProfile']);
    }

    public function searchUsers(Request $request)
    {
        $q = $request->query('q');

        $mechanicIds = collect();
        if (Role::where('name', 'mechanic')->where('guard_name', 'web')->exists()) {
            $mechanicIds = User::role('mechanic')->pluck('id');
        }

        return User::whereNotIn('id', $mechanicIds)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
            })
            ->select('id', 'name', 'email')
            ->limit(15)
            ->get();
    }
}
