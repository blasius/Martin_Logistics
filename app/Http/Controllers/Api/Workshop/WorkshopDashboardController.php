<?php

namespace App\Http\Controllers\Api\Workshop;

use App\Http\Controllers\Controller;
use App\Models\RepairRequest;
use App\Models\RepairRelease;
use App\Models\StockLevel;
use App\Models\RepairAssignment;
use App\Models\User;
use Illuminate\Http\Request;

class WorkshopDashboardController extends Controller
{
    public function index()
    {
        $pendingApproval = RepairRequest::where('status', 'pending_approval')->count();
        $inProgress = RepairRequest::where('status', 'in_progress')->count();
        $completed = RepairRequest::where('status', 'completed')->count();
        $released = RepairRequest::where('status', 'released')->count();
        $critical = RepairRequest::where('priority', 'critical')->whereNotIn('status', ['released', 'cancelled'])->count();

        $releasedPool = RepairRelease::with([
            'repairRequest.vehicle:id,plate_number',
            'repairRequest.mechanic:id,name',
            'releasedBy:id,name',
        ])->latest('released_at')->take(10)->get();

        $recentRequests = RepairRequest::with([
            'vehicle:id,plate_number',
            'mechanic:id,name',
        ])->latest()->take(10)->get();

        $lowStockItems = StockLevel::with(['part:id,name,sku', 'warehouse:id,name'])
            ->whereColumn('quantity', '<=', 'min_quantity')
            ->where('quantity', '>', 0)
            ->orderBy('quantity')
            ->take(10)
            ->get();

        $outOfStockItems = StockLevel::with(['part:id,name,sku', 'warehouse:id,name'])
            ->where('quantity', '<=', 0)
            ->take(10)
            ->get();

        $mechanicsWorkload = User::role('mechanic')
            ->select('id', 'name')
            ->get()
            ->map(function ($mechanic) {
                $mechanic->active_jobs = RepairAssignment::where('mechanic_id', $mechanic->id)
                    ->whereNull('completed_at')
                    ->count();
                return $mechanic;
            });

        return response()->json([
            'stats' => [
                'pending_approval' => $pendingApproval,
                'in_progress' => $inProgress,
                'completed' => $completed,
                'released' => $released,
                'critical' => $critical,
            ],
            'released_pool' => $releasedPool,
            'recent_requests' => $recentRequests,
            'low_stock' => $lowStockItems,
            'out_of_stock' => $outOfStockItems,
            'mechanics_workload' => $mechanicsWorkload,
        ]);
    }
}
