<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\DriverFuelRating;
use App\Models\Expense;
use App\Models\PerformanceScore;
use App\Models\ProofOfDelivery;
use App\Models\RatingSubmission;
use App\Models\Trip;
use App\Models\User;
use App\Models\TripFuelAnalysis;
use Illuminate\Support\Facades\DB;

class RatingService
{
    public function calculateAutomatedScore(Driver $driver, string $periodStart, string $periodEnd): PerformanceScore
    {
        $fuelScore = $this->fuelEfficiencyScore($driver, $periodStart, $periodEnd);
        $deliveryScore = $this->onTimeDeliveryScore($driver, $periodStart, $periodEnd);
        $routeScore = $this->routeComplianceScore($driver, $periodStart, $periodEnd);
        $expenseScore = $this->expenseManagementScore($driver, $periodStart, $periodEnd);
        $safetyScore = $this->safetyScore($driver, $periodStart, $periodEnd);

        $automatedScore = collect([$fuelScore, $deliveryScore, $routeScore, $expenseScore, $safetyScore])
            ->average();

        $humanAgg = $this->humanRatingAggregate($driver, $periodStart, $periodEnd);

        $overall = ($automatedScore * 0.6) + (($humanAgg['avg'] ?? 0) * 20 * 0.4);

        if ($humanAgg['avg'] === null) {
            $overall = $automatedScore;
        }

        return PerformanceScore::updateOrCreate(
            [
                'scoreable_type' => Driver::class,
                'scoreable_id' => $driver->id,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ],
            [
                'overall_score' => round($overall, 2),
                'fuel_efficiency_score' => round($fuelScore, 2),
                'on_time_delivery_score' => round($deliveryScore, 2),
                'route_compliance_score' => round($routeScore, 2),
                'expense_management_score' => round($expenseScore, 2),
                'safety_score' => round($safetyScore, 2),
                'human_rating_avg' => $humanAgg['avg'],
                'human_rating_count' => $humanAgg['count'],
                'automated_score' => round($automatedScore, 2),
                'calculated_at' => now(),
            ]
        );
    }

    public function calculateDispatcherScore(User $user, string $periodStart, string $periodEnd): PerformanceScore
    {
        $tripsManaged = Trip::where('dispatcher_id', $user->id)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $onTimeTrips = Trip::where('dispatcher_id', $user->id)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->where('status', 'completed')
            ->whereNotNull('arrival_time')
            ->whereRaw('arrival_time <= departure_time + interval \'1 day\' * 2')
            ->count();

        $onTimeRate = $tripsManaged > 0 ? ($onTimeTrips / $tripsManaged) * 100 : 0;

        $humanAgg = $this->humanRatingAggregateForUser($user, $periodStart, $periodEnd);

        $automatedScore = $onTimeRate;

        $overall = ($automatedScore * 0.6) + (($humanAgg['avg'] ?? 0) * 20 * 0.4);

        if ($humanAgg['avg'] === null) {
            $overall = $automatedScore;
        }

        return PerformanceScore::updateOrCreate(
            [
                'scoreable_type' => User::class,
                'scoreable_id' => $user->id,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ],
            [
                'overall_score' => round($overall, 2),
                'human_rating_avg' => $humanAgg['avg'],
                'human_rating_count' => $humanAgg['count'],
                'automated_score' => round($automatedScore, 2),
                'calculated_at' => now(),
            ]
        );
    }

    public function leaderboard(string $periodStart, string $periodEnd, int $limit = 20, string $sort = 'desc'): array
    {
        $scores = PerformanceScore::where('scoreable_type', Driver::class)
            ->where('period_start', $periodStart)
            ->where('period_end', $periodEnd)
            ->orderBy('overall_score', $sort)
            ->limit($limit)
            ->get()
            ->map(function ($score) {
                $driver = Driver::find($score->scoreable_id);
                return [
                    'id' => $score->id,
                    'driver_id' => $score->scoreable_id,
                    'driver_name' => $driver?->name ?? 'Unknown',
                    'overall_score' => $score->overall_score,
                    'automated_score' => $score->automated_score,
                    'human_rating_avg' => $score->human_rating_avg,
                    'human_rating_count' => $score->human_rating_count,
                    'fuel_efficiency_score' => $score->fuel_efficiency_score,
                    'on_time_delivery_score' => $score->on_time_delivery_score,
                    'route_compliance_score' => $score->route_compliance_score,
                    'expense_management_score' => $score->expense_management_score,
                    'safety_score' => $score->safety_score,
                ];
            });

        return $scores->toArray();
    }

    public function driverProfile(int $driverId, string $periodStart, string $periodEnd): ?array
    {
        $score = PerformanceScore::where('scoreable_type', Driver::class)
            ->where('scoreable_id', $driverId)
            ->where('period_start', $periodStart)
            ->where('period_end', $periodEnd)
            ->first();

        $driver = Driver::with('user')->find($driverId);
        if (!$driver) return null;

        $submissions = RatingSubmission::where('rateable_type', Driver::class)
            ->where('rateable_id', $driverId)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->with('rater')
            ->orderByDesc('created_at')
            ->get();

        $recentTrips = Trip::where('driver_id', $driverId)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return [
            'driver' => [
                'id' => $driver->id,
                'name' => $driver->name,
                'phone' => $driver->phone,
            ],
            'score' => $score ? [
                'overall_score' => $score->overall_score,
                'fuel_efficiency_score' => $score->fuel_efficiency_score,
                'on_time_delivery_score' => $score->on_time_delivery_score,
                'route_compliance_score' => $score->route_compliance_score,
                'expense_management_score' => $score->expense_management_score,
                'safety_score' => $score->safety_score,
                'human_rating_avg' => $score->human_rating_avg,
                'human_rating_count' => $score->human_rating_count,
                'automated_score' => $score->automated_score,
            ] : null,
            'ratings' => $submissions->map(fn($s) => [
                'id' => $s->id,
                'rating' => $s->rating,
                'category' => $s->category,
                'comment' => $s->comment,
                'rater_name' => $s->rater?->name,
                'created_at' => $s->created_at,
            ]),
            'recent_trips' => $recentTrips->map(fn($t) => [
                'id' => $t->id,
                'reference' => $t->reference,
                'status' => $t->status,
                'is_deviated' => $t->is_deviated,
                'created_at' => $t->created_at,
            ]),
        ];
    }

    public function dispatcherProfile(int $userId, string $periodStart, string $periodEnd): ?array
    {
        $user = User::find($userId);
        if (!$user) return null;

        $score = PerformanceScore::where('scoreable_type', User::class)
            ->where('scoreable_id', $userId)
            ->where('period_start', $periodStart)
            ->where('period_end', $periodEnd)
            ->first();

        $tripsManaged = Trip::where('dispatcher_id', $userId)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $completedTrips = Trip::where('dispatcher_id', $userId)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->where('status', 'completed')
            ->count();

        $submissions = RatingSubmission::where('rateable_type', User::class)
            ->where('rateable_id', $userId)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->with('rater')
            ->orderByDesc('created_at')
            ->get();

        return [
            'dispatcher' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'score' => $score ? [
                'overall_score' => $score->overall_score,
                'automated_score' => $score->automated_score,
                'human_rating_avg' => $score->human_rating_avg,
                'human_rating_count' => $score->human_rating_count,
            ] : null,
            'stats' => [
                'trips_managed' => $tripsManaged,
                'trips_completed' => $completedTrips,
                'completion_rate' => $tripsManaged > 0 ? round(($completedTrips / $tripsManaged) * 100, 1) : 0,
            ],
            'ratings' => $submissions->map(fn($s) => [
                'id' => $s->id,
                'rating' => $s->rating,
                'category' => $s->category,
                'comment' => $s->comment,
                'rater_name' => $s->rater?->name,
                'created_at' => $s->created_at,
            ]),
        ];
    }

    public function batchCalculateAllDrivers(string $periodStart, string $periodEnd): int
    {
        $drivers = Driver::all();
        $count = 0;
        foreach ($drivers as $driver) {
            $this->calculateAutomatedScore($driver, $periodStart, $periodEnd);
            $count++;
        }
        return $count;
    }

    private function fuelEfficiencyScore(Driver $driver, string $periodStart, string $periodEnd): float
    {
        $analysis = TripFuelAnalysis::whereHas('trip', fn($q) => $q->where('driver_id', $driver->id))
            ->whereBetween('analysed_at', [$periodStart, $periodEnd])
            ->select(DB::raw('AVG(variance_percent) as avg_variance'))
            ->first();

        $avgVariance = $analysis?->avg_variance ?? 0;

        if ($avgVariance <= 5) return 100;
        if ($avgVariance <= 10) return 80;
        if ($avgVariance <= 15) return 60;
        if ($avgVariance <= 20) return 40;

        return max(0, 100 - ($avgVariance * 2));
    }

    private function onTimeDeliveryScore(Driver $driver, string $periodStart, string $periodEnd): float
    {
        $totalPods = ProofOfDelivery::whereHas('trip', fn($q) => $q->where('driver_id', $driver->id))
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        if ($totalPods === 0) return 50;

        $onTime = ProofOfDelivery::whereHas('trip', fn($q) => $q->where('driver_id', $driver->id))
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->where('status', 'confirmed')
            ->count();

        return round(($onTime / $totalPods) * 100, 2);
    }

    private function routeComplianceScore(Driver $driver, string $periodStart, string $periodEnd): float
    {
        $totalTrips = Trip::where('driver_id', $driver->id)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        if ($totalTrips === 0) return 50;

        $cleanTrips = Trip::where('driver_id', $driver->id)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->where(function ($q) {
                $q->where('is_deviated', false)->orWhereNull('is_deviated');
            })
            ->count();

        return round(($cleanTrips / $totalTrips) * 100, 2);
    }

    private function expenseManagementScore(Driver $driver, string $periodStart, string $periodEnd): float
    {
        $totalExpenses = Expense::where('driver_id', $driver->user_id)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->sum('amount');

        if ($totalExpenses == 0) return 100;

        $tripCount = Trip::where('driver_id', $driver->id)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        if ($tripCount === 0) return 50;

        $avgPerTrip = $totalExpenses / $tripCount;

        if ($avgPerTrip <= 100) return 100;
        if ($avgPerTrip <= 250) return 80;
        if ($avgPerTrip <= 500) return 60;
        if ($avgPerTrip <= 1000) return 40;

        return max(0, 100 - ($avgPerTrip / 50));
    }

    private function safetyScore(Driver $driver, string $periodStart, string $periodEnd): float
    {
        $ticketCount = \App\Models\SupportTicket::where('subject_type', Trip::class)
            ->whereIn('subject_id', Trip::where('driver_id', $driver->id)
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->pluck('id'))
            ->count();

        if ($ticketCount === 0) return 100;

        $deduction = min($ticketCount * 10, 100);
        return max(0, 100 - $deduction);
    }

    private function humanRatingAggregate(Driver $driver, string $periodStart, string $periodEnd): array
    {
        $avg = RatingSubmission::where('rateable_type', Driver::class)
            ->where('rateable_id', $driver->id)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->avg('rating');

        $count = RatingSubmission::where('rateable_type', Driver::class)
            ->where('rateable_id', $driver->id)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        return [
            'avg' => $avg ? round($avg, 2) : null,
            'count' => $count,
        ];
    }

    private function humanRatingAggregateForUser(User $user, string $periodStart, string $periodEnd): array
    {
        $avg = RatingSubmission::where('rateable_type', User::class)
            ->where('rateable_id', $user->id)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->avg('rating');

        $count = RatingSubmission::where('rateable_type', User::class)
            ->where('rateable_id', $user->id)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        return [
            'avg' => $avg ? round($avg, 2) : null,
            'count' => $count,
        ];
    }
}
