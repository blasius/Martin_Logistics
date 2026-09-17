<?php

namespace App\Services;

use App\Events\DeliveryConfirmed;
use App\Models\ProofOfDelivery;
use App\Models\Trip;
use App\Models\TripHistory;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProofOfDeliveryService
{
    public function __construct(
        protected TripStateMachineService $tripStateMachine,
        protected YardService $yardService,
    ) {}

    public function submit(array $data, ?UploadedFile $photo = null): ProofOfDelivery
    {
        if ($photo) {
            $data['photo_path'] = $photo->store('pod-photos', 'public');
        }

        $data['submitted_by'] = $data['submitted_by'] ?? auth()->id();
        $data['status'] = 'submitted';
        $data['delivered_at'] = $data['delivered_at'] ?? now();

        $pod = ProofOfDelivery::create($data);

        if (!empty($data['trip_id'])) {
            $trip = Trip::find($data['trip_id']);
            if ($trip) {
                $deliveryState = $this->tripStateMachine->configuredStateKey('trip_flow.pod_delivery_state', 'delivered');
                if ($trip->status !== $deliveryState) {
                    $this->tripStateMachine->transition($trip, $deliveryState, [
                        'actor' => \App\Models\User::find($data['submitted_by']),
                        'trigger' => 'system',
                        'strict' => false,
                        'notes' => 'POD submitted',
                    ]);
                }

                TripHistory::create([
                    'trip_id' => $trip->id,
                    'user_id' => $data['submitted_by'],
                    'action' => 'pod_submitted',
                    'changes' => [
                        'pod_id' => $pod->id,
                        'delivered_at' => $data['delivered_at'],
                        'received_by' => $data['received_by_name'],
                    ],
                ]);
            }
        }

        if (!empty($data['order_id'])) {
            $pod->order()->update(['status' => 'delivered']);
        }

        event(new DeliveryConfirmed($pod));

        return $pod->load(['order', 'trip', 'submitter']);
    }

    public function confirm(ProofOfDelivery $pod): ProofOfDelivery
    {
        DB::transaction(function () use ($pod) {
            $pod->update(['status' => 'confirmed']);

            // Point 4: on validated delivery, register expected yard arrival and
            // allocate a free unload dock door for the vehicle.
            $trip = $pod->trip;
            if ($trip) {
                $this->yardService->scheduleUnloadingAfterDelivery($trip, $pod);
            }
        });

        return $pod->fresh()->load(['order', 'trip', 'submitter']);
    }

    /**
     * Reject a submitted POD: send it back to draft so the driver can re-submit,
     * and return the trip to the status it held immediately before this POD was
     * submitted (via the state machine, so history stays consistent).
     */
    public function reject(ProofOfDelivery $pod, string $reason): ProofOfDelivery
    {
        $reason = trim($reason);

        $pod->update([
            'status' => 'draft',
            'notes' => $pod->notes
                ? $pod->notes . "\n[Rejected: {$reason}]"
                : "[Rejected: {$reason}]",
        ]);

        $oldStatus = null;

        if (!empty($pod->trip_id)) {
            $trip = $pod->trip;
            if ($trip) {
                // Find the status held right before this POD pushed the trip forward.
                $previous = TripHistory::where('trip_id', $trip->id)
                    ->where('action', 'status_transition')
                    ->where('changes->new_status', $trip->status)
                    ->orderByDesc('id')
                    ->first();

                $oldStatus = $oldStatus ?? data_get($previous, 'changes.old_status');

                if ($oldStatus && $oldStatus !== $trip->status) {
                    $this->tripStateMachine->transition($trip, $oldStatus, [
                        'actor' => auth()->user(),
                        'trigger' => 'dispatcher',
                        'strict' => false,
                        'notes' => 'POD rejected: ' . $reason,
                    ]);
                }
            }
        }

        TripHistory::create([
            'trip_id' => $pod->trip_id,
            'user_id' => auth()->id() ?? $pod->submitted_by,
            'action' => 'pod_rejected',
            'changes' => [
                'pod_id' => $pod->id,
                'reason' => $reason,
                'returned_to' => $oldStatus,
            ],
        ]);

        if (!empty($pod->order_id)) {
            $order = $pod->order;
            if ($order && $order->status === 'delivered') {
                $order->update(['status' => 'in_transit']);
            }
        }

        return $pod->fresh()->load(['order', 'trip', 'submitter']);
    }

    public function generatePdf(ProofOfDelivery $pod)
    {
        $pod->load(['order.client', 'trip.vehicle', 'trip.driver', 'submitter']);
        return Pdf::loadView('pdf.delivery-receipt', compact('pod'));
    }
}
