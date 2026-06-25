<?php

namespace App\Http\Controllers\Api\Scheduling;

use App\Http\Controllers\Controller;
use App\Models\TimeSlot;
use App\Services\SchedulingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SchedulingController extends Controller
{
    public function __construct(
        protected SchedulingService $schedulingService
    ) {}

    public function events(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after:start',
        ]);

        $start = Carbon::parse($validated['start']);
        $end = Carbon::parse($validated['end']);

        $events = $this->schedulingService->getEvents($start, $end);
        return response()->json($events);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:pickup,delivery,driver_shift,maintenance,dock_reservation,trip',
            'schedulable_type' => 'nullable|string',
            'schedulable_id' => 'nullable|integer',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'driver_id' => 'nullable|exists:users,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'status' => 'nullable|in:scheduled,confirmed,in_progress,completed,cancelled',
            'notes' => 'nullable|string',
            'color' => 'nullable|string|max:7',
        ]);

        if ($validated['vehicle_id'] ?? false) {
            $conflicts = $this->schedulingService->checkConflict(
                $validated['vehicle_id'],
                Carbon::parse($validated['start_time']),
                Carbon::parse($validated['end_time'])
            );

            if (count($conflicts) > 0) {
                return response()->json([
                    'message' => 'Vehicle has a scheduling conflict.',
                    'conflicts' => $conflicts,
                ], 409);
            }
        }

        $validated['created_by'] = $request->user()?->id;
        $slot = TimeSlot::create($validated);

        return response()->json($slot->load(['vehicle:id,plate_number', 'driver:id,name']), 201);
    }

    public function update(Request $request, TimeSlot $timeSlot): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:pickup,delivery,driver_shift,maintenance,dock_reservation,trip',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'driver_id' => 'nullable|exists:users,id',
            'start_time' => 'sometimes|date',
            'end_time' => 'sometimes|date|after:start_time',
            'status' => 'nullable|in:scheduled,confirmed,in_progress,completed,cancelled',
            'notes' => 'nullable|string',
            'color' => 'nullable|string|max:7',
        ]);

        if (($validated['vehicle_id'] ?? false) && ($validated['start_time'] ?? false)) {
            $conflicts = $this->schedulingService->checkConflict(
                $validated['vehicle_id'],
                Carbon::parse($validated['start_time']),
                Carbon::parse($validated['end_time'] ?? $timeSlot->end_time),
                $timeSlot->id
            );
            if (count($conflicts) > 0) {
                return response()->json(['message' => 'Vehicle scheduling conflict.', 'conflicts' => $conflicts], 409);
            }
        }

        $timeSlot->update($validated);
        return response()->json($timeSlot->load(['vehicle:id,plate_number', 'driver:id,name']));
    }

    public function destroy(TimeSlot $timeSlot): JsonResponse
    {
        $timeSlot->delete();
        return response()->json(['message' => 'Time slot deleted.']);
    }
}
