<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $query = Driver::with('user:id,name,email');

        if ($request->search) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            })->orWhere('driving_licence', 'like', '%' . $request->search . '%')
                ->orWhere('phone', 'like', '%' . $request->search . '%');
        }

        return response()->json([
            'drivers' => $query->latest()->paginate(15),
            'stats' => [
                'total' => Driver::count(),
                'male' => Driver::where('sex', 'male')->count(),
                'female' => Driver::where('sex', 'female')->count(),
                'new_this_month' => Driver::whereMonth('created_at', now()->month)->count(),
            ]
        ]);
    }

    /**
     * Emulates Filament Search: Users with 'driver' role not in 'drivers' table.
     */
    public function searchUsers(Request $request)
    {
        $q = $request->query('q');

        return User::whereHas('roles', fn($role) => $role->where('name', 'driver'))
            ->whereNotExists(function ($query) {
                $query->selectRaw(1)
                    ->from('drivers')
                    ->whereRaw('drivers.user_id = users.id');
            })
            ->where('name', 'like', $q . '%')
            ->select('id', 'name', 'email')
            ->limit(10)
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id|unique:drivers,user_id',
            'phone' => 'required|string',
            'whatsapp_phone' => 'nullable|string',
            'driving_licence' => 'required|string|unique:drivers,driving_licence',
            'passport_number' => 'nullable|string|unique:drivers,passport_number',
            'nationality' => 'required|string',
            'sex' => 'required|in:male,female',
            'date_of_birth' => 'required|date',
        ]);

        $driver = Driver::create($validated);

        return response()->json(['message' => 'Driver registered successfully'], 201);
    }
}
