<?php

namespace App\Services;

use App\Models\ReturnRequest;
use App\Models\Invoice;
use App\Models\Client;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    public function create(array $data, array $items): ReturnRequest
    {
        return DB::transaction(function () use ($data, $items) {
            $return = ReturnRequest::create($data);
            foreach ($items as $item) {
                $return->items()->create($item);
            }
            return $return->fresh()->load('items', 'order', 'client');
        });
    }

    public function approve(ReturnRequest $return, int $userId): ReturnRequest
    {
        $return->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
        return $return->fresh();
    }

    public function reject(ReturnRequest $return, string $reason = null): ReturnRequest
    {
        $return->update([
            'status' => 'rejected',
            'notes' => $reason ? ($return->notes . "\nRejection reason: " . $reason) : $return->notes,
        ]);
        return $return->fresh();
    }

    public function schedulePickup(ReturnRequest $return, string $address, string $date, ?int $tripId = null): ReturnRequest
    {
        $return->update([
            'status' => 'pickup_scheduled',
            'pickup_address' => $address,
            'pickup_date' => $date,
            'pickup_trip_id' => $tripId,
        ]);
        return $return->fresh();
    }

    public function receive(ReturnRequest $return, string $condition, array $itemConditions = []): ReturnRequest
    {
        $return->update([
            'status' => 'received',
            'received_at' => now(),
        ]);

        foreach ($itemConditions as $itemId => $conditionValue) {
            $return->items()->where('id', $itemId)->update(['condition' => $conditionValue]);
        }

        return $return->fresh()->load('items');
    }

    public function complete(ReturnRequest $return, string $disposition, ?int $creditNoteId = null): ReturnRequest
    {
        $data = [
            'status' => 'completed',
            'disposition' => $disposition,
        ];

        if ($creditNoteId) {
            $data['credit_note_id'] = $creditNoteId;
        }

        $return->update($data);
        return $return->fresh();
    }

    public function cancel(ReturnRequest $return): ReturnRequest
    {
        $return->update(['status' => 'cancelled']);
        return $return->fresh();
    }

    public function getStats(): array
    {
        return [
            'total' => ReturnRequest::count(),
            'pending' => ReturnRequest::where('status', 'pending')->count(),
            'approved' => ReturnRequest::where('status', 'approved')->count(),
            'received' => ReturnRequest::where('status', 'received')->count(),
            'completed' => ReturnRequest::where('status', 'completed')->count(),
            'by_disposition' => ReturnRequest::whereNotNull('disposition')
                ->selectRaw('disposition, count(*) as total')
                ->groupBy('disposition')
                ->pluck('total', 'disposition'),
        ];
    }
}
