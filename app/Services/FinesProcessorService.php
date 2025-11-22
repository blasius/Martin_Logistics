<?php

namespace App\Services;

use App\Models\TrafficFine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinesProcessorService
{
    public function process(string $plate, $checkedable = null, array $apiResult = []): void
    {
        $status = $apiResult['status'] ?? 'error';
        $tickets = $apiResult['tickets'] ?? [];
        $total = $apiResult['total'] ?? 0;
        $raw = $apiResult['raw'] ?? $apiResult;

        DB::transaction(function () use ($plate, $checkedable, $status, $tickets, $total, $raw) {

            // record fine_check
            \DB::table('fine_checks')->insert([
                'plate_number' => $plate,
                'checked_type' => $checkedable ? get_class($checkedable) : null,
                'checked_id' => $checkedable ? $checkedable->id : null,
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
                            'issued_at' => $ticket['issuedAt'] ?? null,
                            'pay_by' => $ticket['payBy'] ?? null,
                            'status' => $ticket['status'] ?? 'PENDING',
                            'location' => $ticket['locationName'] ?? null,
                            'plate_number' => $ticket['plateNo'] ?? $plate,
                            'raw' => $ticket,
                        ]
                    );

                    if ($checkedable) {
                        $fine->fineable()->associate($checkedable);
                        $fine->save();
                    }

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

            // update last checked
            if ($checkedable) {
                $checkedable->last_fine_check_at = now();
                $checkedable->save();
            }
        });

        Log::info("Processed fines for {$plate}: {$status}, tickets=" . count($tickets));
    }
}
