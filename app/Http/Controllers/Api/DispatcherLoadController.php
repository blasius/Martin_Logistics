<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DispatcherAssignmentService;
use Illuminate\Http\Request;

class DispatcherLoadController extends Controller
{
    public function __construct(protected DispatcherAssignmentService $dispatcherAssignment) {}

    public function index(Request $request)
    {
        $days = $request->integer('days', 30);

        return response()->json([
            'data' => $this->dispatcherAssignment->loadOverview($days),
        ]);
    }
}