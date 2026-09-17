<?php

namespace App\Services;

use App\Models\MechanicProfile;
use App\Models\Trip;
use App\Models\User;
use App\Models\VehicleDispatcherAssignment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AccessReviewService
{
    protected const PERIMETER_ROLES = [
        'portal'   => ['super_admin', 'admin', 'operator', 'operations manager', 'logistics manager', 'director of operations', 'finance officer', 'dispatcher', 'workshop manager'],
        'mobile'   => ['driver', 'mechanic'],
        'customer' => ['customer', 'client'],
    ];

    /**
     * Run the access review and return a normalised summary + findings array.
     *
     * Findings shape:
     * [
     *   'category'     => inactive_user|idle_dispatcher|driver_inactive|mechanic_inactive|orphan_role|locked,
     *   'severity'     => high|medium|low,
     *   'user_id'      => int,
     *   'name'         => string,
     *   'email'        => string,
     *   'roles'        => string[],
     *   'perimeters'   => string[],
     *   'detail'       => string,
     *   'last_activity'=> string|null,
     *   'locked_at'    => string|null,
     * ]
     */
    public function review(): array
    {
        $inactiveDays           = (int) config('access_review.inactive_days');
        $dispatcherInactiveDays = (int) config('access_review.dispatcher_inactive_days');
        $driverInactiveDays     = (int) config('access_review.driver_inactive_days');
        $checkMechanics         = (bool) config('access_review.check_mechanics');
        $protectedRoles         = (array) config('access_review.protected_roles', ['super_admin']);

        $now = now();

        // ── All users + their role names ──
        $users = User::with('roles')->get()->keyBy('id');

        // role (is_active) per user for the orphan-role check
        $roleRows = DB::table('model_has_roles as mhr')
            ->join('roles', 'roles.id', '=', 'mhr.role_id')
            ->select('mhr.model_id as user_id', 'roles.name', 'roles.is_active')
            ->get();

        $roleMeta = $roleRows->groupBy('user_id')->map(fn ($rows) => [
            'names'   => $rows->pluck('name')->values()->all(),
            'inactive_roles' => $rows->where('is_active', false)->pluck('name')->values()->all(),
        ]);

        // ── Last session activity (unix timestamp per user) ──
        $sessionActivity = DB::table('sessions')
            ->whereNotNull('user_id')
            ->selectRaw('user_id, MAX(last_activity) as last_activity')
            ->groupBy('user_id')
            ->pluck('last_activity', 'user_id');

        // ── Dispatcher activity: latest trip per dispatcher ──
        $lastTripByDispatcher = Trip::whereNotNull('dispatcher_id')
            ->selectRaw('dispatcher_id, MAX(created_at) as last_trip_at')
            ->groupBy('dispatcher_id')
            ->pluck('last_trip_at', 'dispatcher_id');

        // ── Driver activity: latest trip per driver ──
        $lastTripByDriver = Trip::whereNotNull('driver_id')
            ->selectRaw('driver_id, MAX(created_at) as last_trip_at')
            ->groupBy('driver_id')
            ->pluck('last_trip_at', 'driver_id');

        // ── Dispatcher vehicle ownership (non-deleted) ──
        $ownedVehicleDispatcherIds = VehicleDispatcherAssignment::query()
            ->pluck('dispatcher_id')
            ->unique()
            ->values()
            ->all();

        // ── Driver vehicle ownership: driver_id (users.id) with open end_date ──
        $assignedDriverIds = DB::table('driver_vehicle_assignments')
            ->whereNull('end_date')
            ->pluck('driver_id')
            ->unique()
            ->values()
            ->all();

        // ── Mechanic profiles keyed by user ──
        $mechanicProfiles = MechanicProfile::pluck('is_active', 'user_id');

        $findings = [];
        $summary  = [
            'total_users'          => $users->count(),
            'with_roles'           => $roleMeta->count(),
            'inactive_users'       => 0,
            'idle_dispatchers'     => 0,
            'drivers_without_vehicle' => 0,
            'mechanics_inactive'   => 0,
            'orphan_roles'         => 0,
            'locked'               => 0,
            'flagged'              => 0,
        ];

        foreach ($users as $userId => $user) {
            $names  = $roleMeta->get($userId)['names'] ?? [];
            $inactiveRoles = $roleMeta->get($userId)['inactive_roles'] ?? [];
            $perimeters = $this->perimetersFor($names);

            if (!$names) {
                // Account with no roles at all — record once as an orphan/inactive signal
                // but skip to avoid noise; role-less users cannot reach any perimeter.
                continue;
            }

            $isProtected = !empty(array_intersect(
                array_map('strtolower', $names),
                array_map('strtolower', $protectedRoles)
            ));

            $lastSessionTs = $sessionActivity->get($userId);
            $lastActivityAt = $lastSessionTs ? Carbon::createFromTimestamp((int) $lastSessionTs) : null;

            $common = [
                'user_id'    => $userId,
                'name'       => $user->name,
                'email'      => $user->email,
                'roles'      => $names,
                'perimeters' => $perimeters,
                'locked_at'  => $user->locked_at?->toISOString(),
            ];

            // 1. Locked accounts are always surfaced for visibility.
            if ($user->locked_at !== null) {
                $summary['locked']++;
                $summary['flagged']++;
                $findings[] = $this->finding($common, 'locked', 'low',
                    'Account locked by access review');
                continue;
            }

            // 2. Orphan role assignments: user holds a deactivated role
            if ($inactiveRoles) {
                $summary['orphan_roles']++;
                $summary['flagged']++;
                $findings[] = $this->finding($common, 'orphan_role', 'medium',
                    'Holds deactivated role(s): ' . implode(', ', $inactiveRoles));
            }

            $hasDriverRole = in_array('Driver', $names, true);
            $hasDispatcherRole = in_array('Dispatcher', $names, true);

            // 3. Drivers: assignment + trip integrity
            if ($hasDriverRole) {
                $hasVehicle = in_array($userId, $assignedDriverIds, true);
                $lastTrip = $lastTripByDriver->get($userId);
                $lastTripAt = $lastTrip ? Carbon::parse($lastTrip) : null;
                $daysSinceTrip = $lastTripAt ? (int) round(abs($now->diffInDays($lastTripAt))) : null;

                if (!$hasVehicle) {
                    $summary['drivers_without_vehicle']++;
                    $summary['flagged']++;

                    if ($daysSinceTrip === null) {
                        $detail = 'Driver role without an active vehicle assignment (no trips on record)';
                        $severity = 'low';
                    } elseif ($daysSinceTrip >= $driverInactiveDays) {
                        $detail = "Driver unassigned; last trip {$daysSinceTrip} days ago";
                        $severity = 'medium';
                    } else {
                        $detail = "Driver unassigned despite a trip {$daysSinceTrip} days ago";
                        $severity = 'high';
                    }

                    $findings[] = $this->finding($common, 'driver_inactive', $severity,
                        $detail, $lastTripAt?->toISOString());
                }
            }

            // 4. Dispatchers: no managed trips + no vehicle ownership
            if ($hasDispatcherRole) {
                $lastTrip = $lastTripByDispatcher->get($userId);
                $lastTripAt = $lastTrip ? Carbon::parse($lastTrip) : null;
                $daysSinceTrip = $lastTripAt ? (int) round(abs($now->diffInDays($lastTripAt))) : null;
                $ownsVehicles = in_array($userId, $ownedVehicleDispatcherIds, true);

                if ($lastTripAt === null || (!$ownsVehicles && $daysSinceTrip >= $dispatcherInactiveDays)) {
                    $summary['idle_dispatchers']++;
                    $summary['flagged']++;
                    $when = $lastTripAt === null
                        ? 'no managed trips on record' . ($ownsVehicles ? '' : ' and no vehicle ownership')
                        : "no managed trips or vehicle ownership for {$daysSinceTrip} days";
                    $findings[] = $this->finding($common, 'idle_dispatcher', 'medium',
                        $when, $lastTripAt?->toISOString());
                }
            }

            // 5. Mechanics: profile missing or inactive
            if ($checkMechanics && in_array('Mechanic', $names, true)) {
                $profile = $mechanicProfiles->get($userId);
                if ($profile === null) {
                    $summary['mechanics_inactive']++;
                    $summary['flagged']++;
                    $findings[] = $this->finding($common, 'mechanic_inactive', 'low',
                        'Mechanic role without a mechanic profile');
                } elseif (!$profile) {
                    $summary['mechanics_inactive']++;
                    $summary['flagged']++;
                    $findings[] = $this->finding($common, 'mechanic_inactive', 'low',
                        'Mechanic profile is deactivated');
                }
            }

            // 6. Inactive portal accounts (non-driver users with stale activity)
            if (!$hasDriverRole && !$isProtected) {
                if ($lastActivityAt === null) {
                    $ageDays = (int) round(abs($now->diffInDays($user->created_at)));
                    if ($ageDays >= $inactiveDays) {
                        $summary['inactive_users']++;
                        $summary['flagged']++;
                        $findings[] = $this->finding($common, 'inactive_user',
                            $ageDays >= $inactiveDays * 2 ? 'high' : 'medium',
                            "Never signed in ({$ageDays} days old account)");
                    }
                } elseif ($lastActivityAt->lt($now->copy()->subDays($inactiveDays))) {
                    $summary['inactive_users']++;
                    $summary['flagged']++;
                    $daysInactive = (int) round(abs($now->diffInDays($lastActivityAt)));
                    $findings[] = $this->finding($common, 'inactive_user',
                        $daysInactive >= $inactiveDays * 2 ? 'high' : 'medium',
                        "No login activity for {$daysInactive} days", $lastActivityAt->toISOString());
                }
            }
        }

        $findings = collect($findings)
            ->sortByDesc(fn ($f) => array_search($f['severity'], ['high', 'medium', 'low'], true))
            ->values()
            ->all();

        return [
            'summary'  => $summary,
            'findings' => $findings,
        ];
    }

    /**
     * Lock accounts eligible for auto-deactivation. Conservative by design:
     * only long-inactive non-protected portal accounts are locked.
     */
    public function autoDeactivate(array $review): int
    {
        $protectedRoles = array_map('strtolower', (array) config('access_review.protected_roles', []));
        $afterDays = (int) config('access_review.auto_deactivate_after_days');
        $cutoff = now()->copy()->subDays($afterDays);

        $eligible = collect($review['findings'])
            ->filter(fn ($f) => $f['category'] === 'inactive_user')
            ->filter(fn ($f) => $f['locked_at'] === null)
            ->filter(function ($f) use ($protectedRoles) {
                foreach ($f['roles'] as $role) {
                    if (in_array(strtolower($role), $protectedRoles, true)) {
                        return false;
                    }
                }
                return true;
            });

        $locked = 0;
        foreach ($eligible as $f) {
            if ($f['last_activity'] === null) {
                // Never signed in: fall back to account creation date.
                $user = User::find($f['user_id']);
                if (!$user || $user->created_at->gt($cutoff)) {
                    continue;
                }
            } else {
                $lastActivity = Carbon::parse($f['last_activity']);
                if ($lastActivity->gt($cutoff)) {
                    continue;
                }
            }

            if (User::whereKey($f['user_id'])->update(['locked_at' => now()])) {
                $locked++;
            }
        }

        return $locked;
    }

    public function perimetersFor(array $roles): array
    {
        $perimeters = [];
        foreach (self::PERIMETER_ROLES as $perimeter => $roleNames) {
            foreach ($roles as $role) {
                if (in_array(strtolower($role), $roleNames, true)) {
                    $perimeters[] = $perimeter;
                    break;
                }
            }
        }
        return array_values(array_unique($perimeters));
    }

    protected function finding(array $common, string $category, string $severity, string $detail, ?string $lastActivity = null): array
    {
        return $common + [
            'category'      => $category,
            'severity'      => $severity,
            'detail'        => $detail,
            'last_activity' => $lastActivity,
        ];
    }
}