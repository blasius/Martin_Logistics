<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\RatingSubmission;
use App\Models\Trip;
use App\Models\User;
use App\Services\RatingService;
use App\Services\TripStateMachineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileRatingController extends Controller
{
    public function __construct(
        protected RatingService $ratingService,
        protected TripStateMachineService $tripStateMachine,
    ) {}

    /**
     * Delivered trips the authenticated user can still rate: a driver rates the
     * trip's dispatcher, a dispatcher rates the trip's driver.
     */
    public function pending(Request $request)
    {
        $user = $request->user();
        $role = $this->ratingRole($user, $request->query('role'));

        if ($role === null) {
            return response()->json(['message' => 'No rating surface is available for this account.'], 403);
        }

        $query = Trip::query()
            ->where('status', $this->tripStateMachine->deliveredStatus())
            ->with(['order', 'driver', 'dispatcher', 'createdBy'])
            ->latest();

        if ($role === 'driver') {
            $query->where('driver_id', $user->driver->id);
        } else {
            $query->where(function ($q) use ($user) {
                $q->where('dispatcher_id', $user->id)->orWhere('created_by', $user->id);
            });
        }

        $trips = $query->limit(50)->get()
            ->map(fn (Trip $trip) => [$trip, $this->targetFor($role, $trip)])
            ->filter(fn (array $pair) => $pair[1] !== null)
            ->reject(fn (array $pair) => $this->ratingService->alreadyRated(
                $user->id,
                $pair[1]['type'],
                $pair[1]['id'],
                $pair[0],
            ))
            ->map(fn (array $pair) => $this->tripPayload($pair[0], $pair[1]))
            ->values();

        return response()->json([
            'message' => $trips->isEmpty() ? 'No trips awaiting your rating.' : 'Trips awaiting your rating.',
            'role' => $role,
            'data' => $trips,
        ]);
    }

    /**
     * Submit the authenticated user's rating for a delivered trip. The subject
     * is the counterparty: the driver rates the dispatcher, the dispatcher
     * rates the driver. One rating per trip per subject.
     */
    public function submit(Request $request)
    {
        $user = $request->user();
        $role = $this->ratingRole($user, $request->input('role'));

        if ($role === null) {
            return response()->json(['message' => 'No rating surface is available for this account.'], 403);
        }

        $validated = $request->validate([
            'trip_id' => 'required|integer|exists:trips,id',
            'rating' => 'required|integer|min:1|max:5',
            'category' => 'nullable|string',
            'comment' => 'nullable|string|max:1000',
        ]);

        $trip = Trip::with(['order', 'driver', 'dispatcher', 'createdBy'])
            ->findOrFail($validated['trip_id']);

        if ($trip->status !== $this->tripStateMachine->deliveredStatus()) {
            return response()->json(['message' => 'You can rate this trip only after it is delivered.'], 422);
        }

        $target = $this->targetFor($role, $trip);

        if ($target === null) {
            return response()->json(['message' => 'You are not linked to this trip.'], 403);
        }

        $categories = $role === 'driver'
            ? RatingService::DISPATCHER_CATEGORIES
            : RatingService::DRIVER_CATEGORIES;

        $category = $validated['category'] ?? 'overall';

        if (!in_array($category, $categories, true)) {
            return response()->json([
                'message' => 'Invalid rating category.',
                'allowed_categories' => $categories,
            ], 422);
        }

        try {
            $submission = $this->ratingService->submit(
                $user,
                $target['type'],
                $target['id'],
                $validated['rating'],
                $category,
                $validated['comment'] ?? null,
                $trip,
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json([
            'message' => 'Rating submitted successfully.',
            'data' => [
                'id' => $submission->id,
                'rating' => (int) $submission->rating,
                'category' => $submission->category,
                'comment' => $submission->comment,
                'rated' => $target['label'],
                'rated_name' => $target['name'],
                'trip_id' => $trip->id,
            ],
        ], 201);
    }

    /**
     * Ratings the authenticated user has received (driver profile or dispatcher).
     */
    public function received(Request $request)
    {
        $user = $request->user();
        $role = $this->ratingRole($user, $request->query('role'));

        if ($role === null) {
            return response()->json(['message' => 'No rating surface is available for this account.'], 403);
        }

        [$type, $id] = $role === 'driver'
            ? [Driver::class, $user->driver->id]
            : [User::class, $user->id];

        $aggregate = DB::table('rating_submissions')
            ->whereNull('deleted_at')
            ->where('rateable_type', $type)
            ->where('rateable_id', $id)
            ->selectRaw('AVG(rating) AS avg, COUNT(*) AS cnt')
            ->first();

        $ratings = RatingSubmission::where('rateable_type', $type)
            ->where('rateable_id', $id)
            ->with('rater')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (RatingSubmission $s) => [
                'id' => $s->id,
                'rating' => (int) $s->rating,
                'category' => $s->category,
                'comment' => $s->comment,
                'rater_name' => $s->rater?->name,
                'created_at' => $s->created_at,
            ]);

        return response()->json([
            'message' => 'Ratings received.',
            'role' => $role,
            'summary' => [
                'average' => $aggregate->avg ? round((float) $aggregate->avg, 2) : null,
                'count' => (int) $aggregate->cnt,
            ],
            'data' => $ratings,
        ]);
    }

    /**
     * Resolve which rating surface the user is acting as. Users with a driver
     * profile rate dispatchers; dispatchers rate drivers. A user holding both
     * roles may pick with ?role=.
     */
    private function ratingRole(User $user, ?string $requested = null): ?string
    {
        $roles = [];

        if ($user->hasRole('Driver') && $user->driver) {
            $roles[] = 'driver';
        }

        if ($user->hasRole('Dispatcher')) {
            $roles[] = 'dispatcher';
        }

        if ($roles === []) {
            return null;
        }

        if ($requested !== null && in_array($requested, $roles, true)) {
            return $requested;
        }

        return $roles[0];
    }

    /**
     * The counterparty a user rates for a trip: the dispatcher when the user is
     * the driver, otherwise the driver. Falls back to the trip creator when no
     * dispatcher is assigned.
     */
    private function targetFor(string $role, Trip $trip): ?array
    {
        if ($role === 'driver') {
            $dispatcher = $trip->dispatcher ?? $trip->createdBy;

            if (!$dispatcher) {
                return null;
            }

            return [
                'type' => User::class,
                'id' => $dispatcher->id,
                'name' => $dispatcher->name,
                'label' => 'dispatcher',
            ];
        }

        if (!$trip->driver) {
            return null;
        }

        return [
            'type' => Driver::class,
            'id' => $trip->driver->id,
            'name' => $trip->driver->name,
            'label' => 'driver',
        ];
    }

    private function tripPayload(Trip $trip, array $target): array
    {
        return [
            'id' => $trip->id,
            'reference' => $trip->reference,
            'status' => $trip->status,
            'origin' => $trip->order?->origin,
            'destination' => $trip->order?->destination,
            'delivered_at' => $trip->arrival_time,
            'ratee' => [
                'type' => $target['type'],
                'id' => $target['id'],
                'name' => $target['name'],
                'role' => $target['label'],
            ],
        ];
    }
}
