<?php

namespace App\Services;

use App\Models\TrafficFine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinesProcessorService
{
    /**
     * Persist the API result. When API returns 'clear' and we have existing unpaid fines for that fineable,
     * we mark them as PAID (keeps history).
     *
     * @param string $plate
     * @param \Illuminate\Database\Eloquent\Model|null $fineable Vehicle|Trailer|null
     * @param array $apiResult
     */
    public function process(string $plate, $fineable = null, array $apiResult = []): void
    {
        $status = $apiResult['status'] ?? 'error';
        $tickets = $apiResult['tickets'] ?? [];
        $total = $apiResult['total'] ?? 0;
        $raw = $apiResult['raw'] ?? $apiResult;

        DB::transaction(function () use ($plate, $fineable, $status, $tickets, $total, $raw) {

            // record check event
            DB::table('fine_checks')->insert([
                'plate_number' => $plate,
                'checkable_type' => $fineable ? get_class($fineable) : null,
                'checkable_id' => $fineable ? $fineable->id : null,
                'result' => $status,
                'ticket_count' => count($tickets),
                'total_amount' => $total,
                'response' => json_encode($raw),
                'created_at' => now(),
            ]);

            // If API says there are no fines -> mark previous unpaid fines as PAID
            if ($status === 'clear' && $fineable) {
                $unpaid = TrafficFine::where('fineable_type', get_class($fineable))
                    ->where('fineable_id', $fineable->id)
                    ->whereIn('status', ['PENDING','UNPAID'])
                    ->get();

                foreach ($unpaid as $f) {
                    $f->status = 'PAID';
                    $f->paid_amount = $f->paid_amount > 0 ? $f->paid_amount : ($f->ticket_amount + ($f->late_fee ?? 0));
                    $f->paid_at = $f->paid_at ?? now();
                    $f->save();
                }
            }

            // If API reports fines -> upsert them and save violations
            if ($status === 'fined') {
                foreach ($tickets as $ticket) {
                    $ticketNumber = $ticket['ticketNumber'] ?? null;

                    // Upsert by ticket_number when available. If null, fallback: create new record.
                    $fine = $ticketNumber
                        ? TrafficFine::firstOrNew(['ticket_number' => $ticketNumber])
                        : new TrafficFine();

                    $fine->ticket_number = $ticketNumber;
                    $fine->ticket_amount = floatval($ticket['ticketAmount'] ?? 0);
                    $fine->late_fee = floatval($ticket['lateFee'] ?? 0);
                    $fine->paid_amount = floatval($ticket['paidAmount'] ?? 0);
                    $fine->issued_at = $ticket['issuedAt'] ?? null;
                    $fine->pay_by = $ticket['payBy'] ?? null;
                    $fine->status = strtoupper($ticket['status'] ?? 'PENDING');
                    $fine->location = $ticket['locationName'] ?? null;
                    $fine->plate_number = $ticket['plateNo'] ?? $plate;
                    $fine->raw = $ticket;

                    if ($fineable) {
                        $fine->fineable()->associate($fineable);
                    }

                    $fine->save();

                    // replace violations
                    $fine->violations()->delete();
                    $violations = $ticket['violations'] ?? [];
                    foreach ($violations as $v) {
                        $fine->violations()->create([
                            'violation_name' => $v['violationName'] ?? null,
                            'violation_name_fr' => $v['violationNameFrench'] ?? null,
                            'violation_name_local' => $v['violationNameKinya'] ?? null,
                            'fine_amount' => floatval($v['fineAmount'] ?? 0),
                            'quantity' => intval($v['quantity'] ?? 0),
                        ]);
                    }
                }
            }

            // Update last_fine_check_at on the entity
            if ($fineable) {
                $fineable->last_fine_check_at = now();
                $fineable->save();
            }
        });

        Log::info("Processed fines for plate {$plate}: status={$status}, tickets=" . count($tickets));
    }
}
