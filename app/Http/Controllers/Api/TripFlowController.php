<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Trip;
use App\Models\TripFlowState;
use App\Models\TripFlowTransition;
use Illuminate\Http\Request;

class TripFlowController extends Controller
{
    public function index()
    {
        $states = TripFlowState::with([
            'outgoingTransitions.toState',
            'outgoingTransitions.fromState',
        ])->orderBy('sort_order')->get();

        $transitions = TripFlowTransition::with(['fromState', 'toState'])
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'states' => $states,
            'transitions' => $transitions,
            'defaults' => [
                'needs_preparation_state' => AppSetting::getValue('trip_flow.needs_preparation_state', 'pre_departure'),
                'ready_to_depart_state' => AppSetting::getValue('trip_flow.ready_to_depart_state', 'assigned'),
                'pod_delivery_state' => AppSetting::getValue('trip_flow.pod_delivery_state', 'delivered'),
            ],
            'triggers' => TripFlowTransition::TRIGGERS,
            'roles' => \Spatie\Permission\Models\Role::orderBy('name')->pluck('name'),
        ]);
    }

    public function storeState(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:50|unique:trip_flow_states,key',
            'label' => 'required|string|max:100',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
            'is_initial' => 'boolean',
            'is_terminal' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $state = TripFlowState::create($validated);

        return response()->json(['message' => 'State created.', 'state' => $state], 201);
    }

    public function updateState(Request $request, TripFlowState $state)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:50|unique:trip_flow_states,key,' . $state->id,
            'label' => 'required|string|max:100',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
            'is_initial' => 'boolean',
            'is_terminal' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $state->update($validated);

        return response()->json(['message' => 'State updated.', 'state' => $state->fresh()]);
    }

    public function destroyState(TripFlowState $state)
    {
        if (Trip::where('status', $state->key)->exists()) {
            return response()->json([
                'message' => "State \"{$state->label}\" is currently used by trips and cannot be removed.",
            ], 422);
        }

        // Cascade soft-delete transitions attached to this state so the graph stays consistent.
        TripFlowTransition::where('from_state_id', $state->id)
            ->orWhere('to_state_id', $state->id)
            ->get()
            ->each->delete();

        $state->delete();

        return response()->json(['message' => 'State removed.']);
    }

    public function reorderStates(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:trip_flow_states,id',
        ]);

        foreach ($validated['ids'] as $index => $id) {
            TripFlowState::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['message' => 'State order updated.']);
    }

    public function storeTransition(Request $request)
    {
        $validated = $this->validateTransition($request);

        $transition = TripFlowTransition::create($validated);

        return response()->json(['message' => 'Transition created.', 'transition' => $transition], 201);
    }

    public function updateTransition(Request $request, TripFlowTransition $transition)
    {
        $validated = $this->validateTransition($request);

        $transition->update($validated);

        return response()->json(['message' => 'Transition updated.', 'transition' => $transition->fresh()]);
    }

    public function destroyTransition(TripFlowTransition $transition)
    {
        $transition->delete();

        return response()->json(['message' => 'Transition removed.']);
    }

    public function updateDefaults(Request $request)
    {
        $validated = $request->validate([
            'needs_preparation_state' => 'required|exists:trip_flow_states,key',
            'ready_to_depart_state' => 'required|exists:trip_flow_states,key',
            'pod_delivery_state' => 'required|exists:trip_flow_states,key',
        ]);

        AppSetting::setValue('trip_flow.needs_preparation_state', $validated['needs_preparation_state'], 'string', 'Trip flow default: state shown under "Needs preparation"');
        AppSetting::setValue('trip_flow.ready_to_depart_state', $validated['ready_to_depart_state'], 'string', 'Trip flow default: state shown under "Ready to depart"');
        AppSetting::setValue('trip_flow.pod_delivery_state', $validated['pod_delivery_state'], 'string', 'Trip flow default: state a trip enters when a POD is submitted');

        return response()->json(['message' => 'Trip flow defaults updated.']);
    }

    private function validateTransition(Request $request): array
    {
        return $request->validate([
            'from_state_id' => 'required|integer|exists:trip_flow_states,id',
            'to_state_id' => 'required|integer|different:from_state_id|exists:trip_flow_states,id',
            'code' => 'nullable|string|max:50',
            'label' => 'required|string|max:100',
            'trigger' => 'required|in:' . implode(',', TripFlowTransition::TRIGGERS),
            'roles' => 'nullable|array',
            'roles.*' => 'string|max:100',
            'records_departure_time' => 'boolean',
            'records_arrival_time' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }
}