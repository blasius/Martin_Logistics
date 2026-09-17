<?php

namespace App\Services;

use App\Exceptions\TripTransitionNotAllowedException;
use App\Models\AppSetting;
use App\Models\Trip;
use App\Models\TripFlowState;
use App\Models\TripFlowTransition;
use App\Models\TripHistory;
use Illuminate\Database\Eloquent\Collection;

class TripStateMachineService
{
    /**
     * The configured initial state new trips enter by default.
     */
    public function initialState(): ?TripFlowState
    {
        return TripFlowState::active()
            ->where('is_initial', true)
            ->orderBy('sort_order')
            ->first()
            ?? TripFlowState::active()->orderBy('sort_order')->first();
    }

    public function stateByKey(string $key): ?TripFlowState
    {
        return TripFlowState::where('key', $key)->first();
    }

    public function stateById(int $id): ?TripFlowState
    {
        return TripFlowState::find($id);
    }

    /**
     * Resolve a configurable operational setting to a state key.
     */
    public function configuredStateKey(string $settingKey, string $default = 'delivered'): string
    {
        return (string) AppSetting::getValue($settingKey, $default);
    }

    /**
     * Keys of all terminal states (trips that no longer flow forward).
     */
    public function terminalStatuses(): array
    {
        return TripFlowState::where('is_terminal', true)
            ->where('is_active', true)
            ->pluck('key')
            ->all();
    }

    /**
     * The configured delivered-state key used for completed / delivered counts.
     */
    public function deliveredStatus(): string
    {
        return $this->configuredStateKey('trip_flow.pod_delivery_state', 'delivered');
    }

    public function isTerminal(Trip $trip, ?TripFlowState $state = null): bool
    {
        $state = $state ?? $this->stateByKey($trip->status);
        return (bool) ($state?->is_terminal ?? false);
    }

    /**
     * All active transitions leaving a given status key.
     */
    public function transitionsFrom(string $statusKey, ?string $trigger = null): Collection
    {
        return TripFlowTransition::query()
            ->where('is_active', true)
            ->whereHas('fromState', fn ($q) => $q->where('key', $statusKey)->where('is_active', true))
            ->when($trigger, fn ($q) => $q->whereIn('trigger', [$trigger, 'any']))
            ->orderBy('sort_order')
            ->get();
    }

    public function availableTransitions(Trip $trip, array $options = []): Collection
    {
        return $this->transitionsFrom($trip->status, $options['trigger'] ?? null);
    }

    /**
     * Whether $to (transition code | transition id | transition model | destination state key)
     * is currently allowed for the trip.
     */
    public function canTransition(Trip $trip, mixed $to, array $options = []): bool
    {
        $transition = $this->resolveTransition($trip, $to);

        if (!$transition) {
            return false;
        }

        $trigger = $options['trigger'] ?? 'any';
        if ($trigger !== 'any' && $transition->trigger !== 'any' && $transition->trigger !== $trigger) {
            return false;
        }

        if ($trigger !== 'any' && !empty($transition->roles) && !empty($options['actor'])) {
            $roles = $options['actor']->getRoleNames()->all();
            if (!array_intersect($transition->roles, $roles)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Perform a transition. When $strict is false and no matching configured
     * transition exists, the status is still written directly (legacy fallback)
     * but flagged in the history so config gaps remain visible.
     */
    public function transition(Trip $trip, mixed $to, array $options = []): Trip
    {
        $strict = $options['strict'] ?? true;
        $transition = $this->resolveTransition($trip, $to);

        if ($transition) {
            if (!$this->canTransition($trip, $transition, $options)) {
                throw new TripTransitionNotAllowedException($trip, $to);
            }
            $toState = $transition->toState;
        } elseif ($strict) {
            throw new TripTransitionNotAllowedException($trip, $to);
        }

        $toState = $toState ?? $this->resolveTargetState($to);
        if (!$toState) {
            throw new TripTransitionNotAllowedException($trip, $to);
        }

        $updates = ['status' => $toState->key];
        if ($transition && $transition->records_departure_time && !$trip->departure_time) {
            $updates['departure_time'] = now();
        }
        if ($transition && $transition->records_arrival_time && !$trip->arrival_time) {
            $updates['arrival_time'] = now();
        }

        $oldStatus = $trip->status;
        $trip->update($updates);

        TripHistory::create([
            'trip_id' => $trip->id,
            'user_id' => ($options['actor'] ?? auth()->user())?->id ?? $trip->created_by,
            'action' => 'status_transition',
            'changes' => [
                'old_status' => $oldStatus,
                'new_status' => $toState->key,
                'transition' => $transition->label ?? null,
                'transition_code' => $transition->code ?? null,
                'notes' => $options['notes'] ?? null,
                'warning' => $transition ? null : 'transition_not_configured',
            ],
        ]);

        return $trip->fresh();
    }

    /**
     * Transition by a stable transition code (e.g. mark_ready, depart, arrive).
     */
    public function transitionByCode(Trip $trip, string $code, array $options = []): Trip
    {
        return $this->transition($trip, $code, $options);
    }

    /**
     * Record a trip's status against the configured flow without validating a
     * transition (used when creating a trip). Logs a status_set history entry.
     */
    public function recordStatus(Trip $trip, string $statusKey, array $options = []): Trip
    {
        if ($trip->status !== $statusKey) {
            $trip->update(['status' => $statusKey]);
        }

        TripHistory::create([
            'trip_id' => $trip->id,
            'user_id' => ($options['actor'] ?? auth()->user())?->id ?? $trip->created_by,
            'action' => $options['action'] ?? 'status_set',
            'changes' => [
                'new_status' => $statusKey,
                'notes' => $options['notes'] ?? null,
            ],
        ]);

        return $trip->fresh();
    }

    private function resolveTransition(Trip $trip, mixed $to): ?TripFlowTransition
    {
        if ($to instanceof TripFlowTransition) {
            return $to;
        }

        if (is_int($to)) {
            $transition = TripFlowTransition::find($to);
            if ($transition && $transition->fromState?->key === $trip->status) {
                return $transition;
            }
            return null;
        }

        if (is_string($to)) {
            $base = TripFlowTransition::query()
                ->where('is_active', true)
                ->whereHas('fromState', fn ($q) => $q->where('key', $trip->status)->where('is_active', true));

            $byCode = (clone $base)->where('code', $to)->first();
            if ($byCode) {
                return $byCode;
            }

            $target = $this->stateByKey($to);
            if (!$target) {
                return null;
            }

            return (clone $base)->where('to_state_id', $target->id)->first();
        }

        return null;
    }

    private function resolveTargetState(mixed $to): ?TripFlowState
    {
        if ($to instanceof TripFlowState) {
            return $to;
        }
        if (is_string($to)) {
            return $this->stateByKey($to);
        }
        if (is_int($to)) {
            return $this->stateById($to);
        }
        return null;
    }
}