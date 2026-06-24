<?php

namespace App\Services;

use App\Models\ProofOfDelivery;
use App\Models\Trip;
use App\Models\TripHistory;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProofOfDeliveryService
{
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
            if ($trip && $trip->status !== 'delivered') {
                $trip->update(['status' => 'delivered']);
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

        return $pod->load(['order', 'trip', 'submitter']);
    }

    public function confirm(ProofOfDelivery $pod): ProofOfDelivery
    {
        $pod->update(['status' => 'confirmed']);
        return $pod->fresh()->load(['order', 'trip', 'submitter']);
    }

    public function generatePdf(ProofOfDelivery $pod)
    {
        $pod->load(['order.client', 'trip.vehicle', 'trip.driver', 'submitter']);
        return Pdf::loadView('pdf.delivery-receipt', compact('pod'));
    }
}
