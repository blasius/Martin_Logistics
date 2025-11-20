<?php

namespace App\Services;

use App\Models\TrafficFine;
use App\Models\TrafficFineViolation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinesProcessorService
{
    /**
     * Persist and process normalized API result.
     *
     * @param string $plate
     * @param \Illuminate\Database\Eloquent\Model|null $checkedable Vehicle|Trailer or null
     * @param array $apiResult normalized result from FinesApiService
     */
    public function process(string $plate, $checkedable = null, array $apiResult = []): void
    {
        $status = $apiResult['status'] ?? 'error';
        $tickets = $apiResult['tickets'] ?? [];
        $total = $apiResult['total'] ?? 0;
        $raw = $apiResult['raw'] ?? $apiResult;

        DB::transaction(function () use ($plate, $checkedable, $status, $tickets, $total, $raw) {
            // Insert audit row into fine_checks
            DB::table('fine_checks')->insert([
                'plate_number' => $plate,
                'checkedable_type' => $checkedable ? get_class($checkedable) : null,
                'checkedable_id' => $checkedable ? $checkedable->id : null,
                'result' => $status,
                'ticket_count' => count($tickets),
                'total_amount' => $total,
                'response' => json_encode($raw),
                'created_at' => now(),
            ]);

            if ($status === 'fined') {
                foreach ($tickets as $ticket) {
                    $ticketNumber = $ticket['ticketNumber'] ?? null;

                    $fine = TrafficFine::updateOrCreate(
                        ['ticket_number' => $ticketNumber],
                        [
                            'ticket_number' => $ticketNumber,
                            'ticket_amount' => floatval($ticket['ticketAmount'] ?? 0),
                            'late_fee' => floatval($ticket['lateFee'] ?? 0),
                            'paid_amount' => floatval($ticket['paidAmount'] ?? 0),
                            'issued_at' => isset($ticket['issuedAt']) ? $ticket['issuedAt'] : null,
                            'pay_by' => isset($ticket['payBy']) ? $ticket['payBy'] : null,
                            'status' => $ticket['status'] ?? null,
                            'location' => $ticket['locationName'] ?? null,
                            'raw' => $ticket,
                        ]
                    );

                    if ($checkedable) {
                        $fine->finedable()->associate($checkedable);
                        $fine->save();
                    }

                    // Replace violations for this fine
                    $fine->violations()->delete();
                    $violations = $ticket['violations'] ?? [];
                    foreach ($violations as $v) {
                        $fine->violations()->create([
                            'violation_name' => $v['violationName'] ?? $v['violationNameFrench'] ?? $v['violationNameKinya'] ?? null,
                            'fine_amount' => floatval($v['fineAmount'] ?? 0),
                            'quantity' => intval($v['quantity'] ?? 0),
                        ]);
                    }
                }
            }

            // Update last_fine_check_at on checkedable
            if ($checkedable) {
                $checkedable->last_fine_check_at = now();
                $checkedable->save();
            }
        });

        Log::info("Fines processed for {$plate}: {$status}, tickets=" . count($tickets));
    }
}
