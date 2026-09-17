<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

        $total = Driver::count();
        $activeIds = Driver::whereHas('vehicles', function ($q) {
            $q->whereNull('driver_vehicle_assignments.end_date');
        })->pluck('id');
        $active = $activeIds->count();
        $inactive = max(0, $total - $active);

        $male = Driver::where('sex', 'male')->count();
        $female = Driver::where('sex', 'female')->count();
        $maleActive = Driver::where('sex', 'male')->whereIn('id', $activeIds)->count();
        $femaleActive = Driver::where('sex', 'female')->whereIn('id', $activeIds)->count();

        $nationalities = Driver::whereNotNull('nationality')
            ->get(['nationality', 'id'])
            ->groupBy('nationality')
            ->map(function ($drivers, $nationality) use ($activeIds) {
                return [
                    'nationality' => $nationality,
                    'active' => $drivers->whereIn('id', $activeIds)->count(),
                    'inactive' => $drivers->whereNotIn('id', $activeIds)->count(),
                ];
            })
            ->values()
            ->toArray();

        return response()->json([
            'drivers' => $query->latest()->paginate(15),
            'stats' => [
                'total' => $total,
                'active' => $active,
                'inactive' => $inactive,
                'active_pct' => $total ? round(($active / $total) * 100) : 0,
                'inactive_pct' => $total ? round(($inactive / $total) * 100) : 0,
                'male' => $male,
                'female' => $female,
                'sex_active' => ['male' => $maleActive, 'female' => $femaleActive],
                'sex_inactive' => ['male' => $male - $maleActive, 'female' => $female - $femaleActive],
                'new_this_month' => Driver::whereMonth('created_at', now()->month)->count(),
                'nationalities' => $nationalities,
            ]
        ]);
    }

    public function searchUsers(Request $request)
    {
        $q = $request->query('q');
        return User::whereHas('roles', fn($role) => $role->where('name', 'driver'))
            ->whereNotExists(function ($query) {
                $query->selectRaw(1)->from('drivers')->whereRaw('drivers.user_id = users.id');
            })
            ->where('name', 'like', $q . '%')
            ->select('id', 'name', 'email')
            ->limit(10)->get();
    }

    public function store(Request $request)
    {
        // 1. Validate - ensure these keys match the names in your Vue 'form' object
        $validated = $request->validate([
            'user_id'         => 'required|exists:users,id|unique:drivers,user_id',
            'phone'           => 'required|string',
            'whatsapp_phone'  => 'nullable|string',
            'driving_licence' => 'required|string',
            'licence_expiry'  => 'required|date',
            'passport_number' => 'nullable|string',
            'passport_expiry' => 'nullable|date',
            'nationality'     => 'required|string',
            'sex'             => 'required|in:male,female',
            'date_of_birth'   => 'required|date',
        ]);

        // 2. Handle Files separately to get the paths
        if ($request->hasFile('licence_file')) {
            $validated['licence_file'] = $request->file('licence_file')->store('drivers/licences', 'public');
        }

        if ($request->hasFile('passport_file')) {
            $validated['passport_file'] = $request->file('passport_file')->store('drivers/passports', 'public');
        }

        // 3. Create the record
        $driver = Driver::create($validated);

        return response()->json(['message' => 'Driver record and documents saved'], 201);
    }
}
