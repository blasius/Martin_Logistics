<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $query = Driver::with('user:id,name,email');

        // Search logic (Search by name in the users table or license in drivers)
        if ($request->search) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            })->orWhere('driving_licence', 'like', '%' . $request->search . '%')
                ->orWhere('phone', 'like', '%' . $request->search . '%');
        }

        $drivers = $query->latest()->paginate(15);

        return response()->json([
            'drivers' => $drivers,
            'stats' => [
                'total' => Driver::count(),
                'male' => Driver::where('sex', 'male')->count(),
                'female' => Driver::where('sex', 'female')->count(),
                'new_this_month' => Driver::whereMonth('created_at', now()->month)->count(),
            ]
        ]);
    }
}
