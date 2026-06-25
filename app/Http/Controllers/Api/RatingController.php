<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\PerformanceScore;
use App\Models\RatingSubmission;
use App\Models\User;
use App\Services\RatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function __construct(protected RatingService $ratingService) {}

    public function leaderboard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'limit' => 'nullable|integer|min:1|max:100',
            'sort' => 'nullable|in:asc,desc',
        ]);

        $data = $this->ratingService->leaderboard(
            $validated['period_start'],
            $validated['period_end'],
            $validated['limit'] ?? 20,
            $validated['sort'] ?? 'desc'
        );

        return response()->json(['data' => $data]);
    }

    public function driverProfile(Request $request, int $driverId): JsonResponse
    {
        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        $profile = $this->ratingService->driverProfile(
            $driverId,
            $validated['period_start'],
            $validated['period_end']
        );

        if (!$profile) {
            return response()->json(['message' => 'Driver not found'], 404);
        }

        return response()->json(['data' => $profile]);
    }

    public function dispatcherProfile(Request $request, int $userId): JsonResponse
    {
        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        $profile = $this->ratingService->dispatcherProfile(
            $userId,
            $validated['period_start'],
            $validated['period_end']
        );

        if (!$profile) {
            return response()->json(['message' => 'Dispatcher not found'], 404);
        }

        return response()->json(['data' => $profile]);
    }

    public function submitRating(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rateable_type' => 'required|string|in:App\\Models\\Driver,App\\Models\\User',
            'rateable_id' => 'required|integer',
            'rating' => 'required|integer|min:1|max:5',
            'category' => 'required|string|in:fuel_efficiency,on_time_delivery,route_compliance,expense_management,safety,overall',
            'comment' => 'nullable|string|max:1000',
            'submission_context_type' => 'nullable|string',
            'submission_context_id' => 'nullable|integer',
        ]);

        $validated['rater_id'] = $request->user()->id;

        $submission = RatingSubmission::create($validated);

        return response()->json([
            'message' => 'Rating submitted successfully',
            'data' => $submission->load('rater'),
        ], 201);
    }

    public function calculateScores(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        $count = $this->ratingService->batchCalculateAllDrivers(
            $validated['period_start'],
            $validated['period_end']
        );

        return response()->json([
            'message' => "Scores calculated for {$count} drivers",
            'count' => $count,
        ]);
    }

    public function calculateDriverScore(Request $request, int $driverId): JsonResponse
    {
        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        $driver = Driver::find($driverId);
        if (!$driver) {
            return response()->json(['message' => 'Driver not found'], 404);
        }

        $score = $this->ratingService->calculateAutomatedScore(
            $driver,
            $validated['period_start'],
            $validated['period_end']
        );

        return response()->json([
            'message' => 'Score calculated',
            'data' => $score,
        ]);
    }

    public function calculateDispatcherScore(Request $request, int $userId): JsonResponse
    {
        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $score = $this->ratingService->calculateDispatcherScore(
            $user,
            $validated['period_start'],
            $validated['period_end']
        );

        return response()->json([
            'message' => 'Dispatcher score calculated',
            'data' => $score,
        ]);
    }

    public function topDrivers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $data = $this->ratingService->leaderboard(
            $validated['period_start'],
            $validated['period_end'],
            $validated['limit'] ?? 10,
            'desc'
        );

        return response()->json(['data' => $data]);
    }

    public function bottomDrivers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $data = $this->ratingService->leaderboard(
            $validated['period_start'],
            $validated['period_end'],
            $validated['limit'] ?? 10,
            'asc'
        );

        return response()->json(['data' => $data]);
    }

    public function availableDrivers(): JsonResponse
    {
        $drivers = Driver::with('user')->orderBy('id')->get()->map(fn($d) => [
            'id' => $d->id,
            'name' => $d->name,
            'phone' => $d->phone,
        ]);

        return response()->json(['data' => $drivers]);
    }

    public function availableDispatchers(): JsonResponse
    {
        $dispatchers = User::role('Dispatcher')->get()->map(fn($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
        ]);

        return response()->json(['data' => $dispatchers]);
    }
}
