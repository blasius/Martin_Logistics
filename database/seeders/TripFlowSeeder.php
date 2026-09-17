<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\TripFlowState;
use App\Models\TripFlowTransition;
use Illuminate\Database\Seeder;

class TripFlowSeeder extends Seeder
{
    // Sensible defaults. Every aspect below is configurable in the back office
    // (Portal → Trips → Trip Flow), so customise there instead of editing code.
    private array $states = [
        ['key' => 'pre_departure', 'label' => 'Pre Departure', 'color' => '#f59e0b', 'description' => 'Trip created, awaiting dispatch preparation', 'is_initial' => true, 'is_terminal' => false, 'sort_order' => 1],
        ['key' => 'pending', 'label' => 'Pending', 'color' => '#64748b', 'description' => 'Awaiting resource assignment', 'is_initial' => false, 'is_terminal' => false, 'sort_order' => 2],
        ['key' => 'assigned', 'label' => 'Assigned', 'color' => '#3b82f6', 'description' => 'Vehicle and/or driver assigned, ready to depart', 'is_initial' => false, 'is_terminal' => false, 'sort_order' => 3],
        ['key' => 'on_route', 'label' => 'On Route', 'color' => '#d97706', 'description' => 'Trip in transit', 'is_initial' => false, 'is_terminal' => false, 'sort_order' => 4],
        ['key' => 'delivered', 'label' => 'Delivered', 'color' => '#10b981', 'description' => 'Successfully delivered', 'is_initial' => false, 'is_terminal' => true, 'sort_order' => 5],
        ['key' => 'cancelled', 'label' => 'Cancelled', 'color' => '#ef4444', 'description' => 'Trip cancelled', 'is_initial' => false, 'is_terminal' => true, 'sort_order' => 6],
    ];

    private array $transitions = [
        ['from' => 'pre_departure', 'to' => 'assigned', 'code' => 'mark_ready', 'label' => 'Mark Ready', 'trigger' => 'dispatcher', 'records_departure_time' => false, 'records_arrival_time' => false, 'sort_order' => 1],
        ['from' => 'pre_departure', 'to' => 'pending', 'code' => 'requeue', 'label' => 'Queue as Pending', 'trigger' => 'dispatcher', 'records_departure_time' => false, 'records_arrival_time' => false, 'sort_order' => 2],
        ['from' => 'pending', 'to' => 'pre_departure', 'code' => 'start_prep', 'label' => 'Start Preparation', 'trigger' => 'dispatcher', 'records_departure_time' => false, 'records_arrival_time' => false, 'sort_order' => 3],
        ['from' => 'pending', 'to' => 'assigned', 'code' => 'assign', 'label' => 'Assign Resources', 'trigger' => 'dispatcher', 'records_departure_time' => false, 'records_arrival_time' => false, 'sort_order' => 4],
        ['from' => 'assigned', 'to' => 'on_route', 'code' => 'depart', 'label' => 'Depart', 'trigger' => 'driver', 'records_departure_time' => true, 'records_arrival_time' => false, 'sort_order' => 5],
        ['from' => 'on_route', 'to' => 'delivered', 'code' => 'arrive', 'label' => 'Arrive & Deliver', 'trigger' => 'driver', 'records_departure_time' => false, 'records_arrival_time' => true, 'sort_order' => 6],
        ['from' => 'on_route', 'to' => 'delivered', 'code' => 'confirm_pod', 'label' => 'POD Confirmed', 'trigger' => 'system', 'records_departure_time' => false, 'records_arrival_time' => true, 'sort_order' => 7],
        ['from' => 'pre_departure', 'to' => 'cancelled', 'code' => 'cancel_prep', 'label' => 'Cancel', 'trigger' => 'dispatcher', 'records_departure_time' => false, 'records_arrival_time' => false, 'sort_order' => 8],
        ['from' => 'pending', 'to' => 'cancelled', 'code' => 'cancel_pending', 'label' => 'Cancel', 'trigger' => 'dispatcher', 'records_departure_time' => false, 'records_arrival_time' => false, 'sort_order' => 9],
        ['from' => 'assigned', 'to' => 'cancelled', 'code' => 'cancel_assigned', 'label' => 'Cancel', 'trigger' => 'dispatcher', 'records_departure_time' => false, 'records_arrival_time' => false, 'sort_order' => 10],
        ['from' => 'on_route', 'to' => 'cancelled', 'code' => 'cancel_route', 'label' => 'Cancel', 'trigger' => 'dispatcher', 'records_departure_time' => false, 'records_arrival_time' => false, 'sort_order' => 11],
    ];

    public function run(): void
    {
        $stateIds = [];

        foreach ($this->states as $data) {
            $state = TripFlowState::updateOrCreate(
                ['key' => $data['key']],
                $data,
            );
            $stateIds[$data['key']] = $state->id;
        }

        foreach ($this->transitions as $data) {
            $fromId = $stateIds[$data['from']];
            $toId = $stateIds[$data['to']];

            TripFlowTransition::updateOrCreate(
                [
                    'from_state_id' => $fromId,
                    'to_state_id' => $toId,
                    'code' => $data['code'],
                ],
                [
                    'from_state_id' => $fromId,
                    'to_state_id' => $toId,
                    'code' => $data['code'],
                    'label' => $data['label'],
                    'trigger' => $data['trigger'],
                    'records_departure_time' => $data['records_departure_time'],
                    'records_arrival_time' => $data['records_arrival_time'],
                    'is_active' => true,
                    'sort_order' => $data['sort_order'],
                ],
            );
        }

        // Operational defaults used to wire automation into the configurable flow.
        $defaults = [
            'trip_flow.needs_preparation_state' => 'pre_departure',
            'trip_flow.ready_to_depart_state' => 'assigned',
            'trip_flow.pod_delivery_state' => 'delivered',
        ];

        foreach ($defaults as $key => $default) {
            AppSetting::firstOrCreate(
                ['key' => $key],
                ['value' => $default, 'type' => 'string', 'description' => 'Trip flow operational default (editable in Portal → Trips → Trip Flow)'],
            );
        }
    }
}