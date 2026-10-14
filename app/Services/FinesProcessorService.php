<?php

namespace App\Services;

use App\Models\SupportTicket;
use App\Models\TrafficFine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinesProcessorService
{
    public function __construct(protected SupportAutoTicketService $autoTickets) {}

    /**
     * Persist an API result for a plate.
     *
     * Irembo only ever returns UNPAID tickets — it never reports a paid
     * status, it simply drops a ticket from the list once it is settled.
     * Reconciliation is therefore per ticket number, not per plate: on every
     * response we settle any locally-outstanding fine for this unit whose
     * ticket is absent. A single 'clear' response settles everything, but so
     * does a partial response where only some tickets were paid.
     *
     * @param string $plate
     * @param \Illuminate\Database\Eloquent\Model|null $fineable Vehicle|Trailer|null
     * @param array $apiResult
     */
    public function process(string $plate, $fineable = null, array $apiResult = []): void
    {
        $status = $apiResult['status'] ?? 'error';
        $tickets = $apiResult['status'] === 'fined' ? ($apiResult['tickets'] ?? []) : [];
        $total = $apiResult['total'] ?? 0;
        $raw = $apiResult['raw'] ?? $apiResult;

        DB::transaction(function () use ($plate, $fineable, $status, $tickets, $total, $raw) {

            // 1. Reconcile what we hold against what the portal just returned.
            //    Runs for both 'clear' and 'fined' so a partial settlement is
            //    picked up instead of waiting for the plate to go fully clear.
            $this->settleAbsentTickets($plate, $fineable, $tickets);

            // 2. Upsert the tickets the portal still reports as outstanding.
            if ($status === 'fined') {
                foreach ($tickets as $ticket) {
                    $fine = $this->findOrCreateFine($ticket, $plate);

                    $fine->ticket_amount = floatval($ticket['ticketAmount'] ?? 0);
                    $fine->late_fee = floatval($ticket['lateFee'] ?? 0);
                    $fine->issued_at = $this->parseDate($ticket['issuedAt'] ?? null);
                    $fine->pay_by = $this->parseDate($ticket['payBy'] ?? null);
                    $fine->location = $ticket['locationName'] ?? null;
                    $fine->plate_number = $ticket['plateNo'] ?? $plate;
                    $fine->raw = $ticket;

                    $this->applyPortalPaymentState($fine, $ticket);

                    if ($fineable) {
                        $fine->fineable()->associate($fineable);
                    }

                    $fine->save();

                    $this->recordCheck($fine, $plate, $fineable, $ticket);
                    $this->syncViolations($fine, $ticket);
                }
            }

            // Update last_fine_check_at on the entity
            if ($fineable) {
                $fineable->last_fine_check_at = now();
                $fineable->save();
            }
        });

        if ($status === 'error') {
            $this->openFailureTicket($plate, $fineable, $apiResult);
        }

        Log::info("Processed fines for plate {$plate}: status={$status}, tickets=" . count($tickets));
    }

    /**
     * Settle locally-outstanding fines that the portal no longer reports.
     *
     * A fine is considered settled when its ticket number is absent from the
     * response. For the rare ticket without a number we fall back to a
     * composite of plate + issue date + amount so repeated polls still
     * reconcile to the same row instead of duplicating it.
     *
     * @return int number of fines settled
     */
    protected function settleAbsentTickets(string $plate, $fineable, array $tickets): int
    {
        $query = TrafficFine::outstanding();

        if ($fineable) {
            $query->where(function ($q) use ($fineable, $plate) {
                $q->where(function ($sub) use ($fineable) {
                    $sub->where('fineable_type', $fineable->getMorphClass())
                        ->where('fineable_id', $fineable->getKey());
                })->orWhere('plate_number', $plate);
            });
        } else {
            $query->where('plate_number', $plate);
        }

        $candidates = $query->get();

        if ($candidates->isEmpty()) {
            return 0;
        }

        $returnedNumbers = [];
        $returnedSignatures = [];

        foreach ($tickets as $ticket) {
            $number = $ticket['ticketNumber'] ?? null;

            if ($number) {
                $returnedNumbers[] = (string) $number;
            }

            $returnedSignatures[] = $this->signature(
                $ticket['plateNo'] ?? $plate,
                $this->parseDate($ticket['issuedAt'] ?? null),
                floatval($ticket['ticketAmount'] ?? 0)
            );
        }

        $settled = 0;

        foreach ($candidates as $fine) {
            $stillOwed = $fine->ticket_number
                ? in_array((string) $fine->ticket_number, $returnedNumbers, true)
                : in_array(
                    $this->signature($fine->plate_number, $fine->issued_at, (float) $fine->ticket_amount),
                    $returnedSignatures,
                    true
                );

            if ($stillOwed) {
                continue;
            }

            $settled += $fine->settleAsPaid() ? 1 : 0;
        }

        if ($settled > 0) {
            Log::info("Settled {$settled} traffic fine(s) no longer reported for plate {$plate}");
        }

        return $settled;
    }

    /**
     * Apply payment fields the portal does supply.
     *
     * The portal never sends a status; payment is signalled by `paidAt` and
     * `paidAmount`. Note that `paymentAmount` is NOT a payment — it is the
     * amount still owed (ticket amount, plus any late fee once the portal has
     * applied one), so it must never be read as a settlement.
     */
    protected function applyPortalPaymentState(TrafficFine $fine, array $ticket): void
    {
        $paidAt = $this->parseDateTime($ticket['paidAt'] ?? null);
        $paidAmount = floatval($ticket['paidAmount'] ?? 0);

        $isPaid = $paidAt !== null || $paidAmount > 0;

        if ($isPaid) {
            $fine->settleAsPaid($paidAt, $paidAmount > 0 ? $paidAmount : null);

            return;
        }

        // Still outstanding at the portal. Never reopen a fine we have already
        // settled locally — the portal lags and would otherwise flip a paid
        // fine back to unpaid on the next sweep.
        if (!$fine->isSettled()) {
            $fine->status = TrafficFine::STATUS_PENDING;
        }
    }

    /**
     * Locate the existing row for a portal ticket.
     *
     * Ticket number is the natural key. Tickets arriving without one are
     * matched on plate + issue date + amount so we update in place rather
     * than inserting a duplicate on every sweep.
     */
    protected function findOrCreateFine(array $ticket, string $plate): TrafficFine
    {
        $number = $ticket['ticketNumber'] ?? null;

        if ($number) {
            $existing = TrafficFine::where('ticket_number', $number)->first();

            if ($existing) {
                return $existing;
            }

            $fine = new TrafficFine();
            $fine->ticket_number = $number;

            return $fine;
        }

        $signature = $this->signature(
            $ticket['plateNo'] ?? $plate,
            $this->parseDate($ticket['issuedAt'] ?? null),
            floatval($ticket['ticketAmount'] ?? 0)
        );

        $existing = TrafficFine::whereNull('ticket_number')
            ->where('plate_number', $ticket['plateNo'] ?? $plate)
            ->whereDate('issued_at', $this->parseDate($ticket['issuedAt'] ?? null))
            ->where('ticket_amount', floatval($ticket['ticketAmount'] ?? 0))
            ->first();

        if ($existing) {
            return $existing;
        }

        Log::warning("Fines portal returned a ticket without a ticket number for plate {$plate} ({$signature})");

        return new TrafficFine();
    }

    protected function syncViolations(TrafficFine $fine, array $ticket): void
    {
        $fine->violations()->delete();

        foreach ($ticket['violations'] ?? [] as $v) {
            $fine->violations()->create([
                'violation_name' => $v['violationName'] ?? null,
                'violation_name_fr' => $v['violationNameFrench'] ?? null,
                'violation_name_local' => $v['violationNameKinya'] ?? null,
                'fine_amount' => floatval($v['fineAmount'] ?? 0),
                'quantity' => intval($v['quantity'] ?? 0),
            ]);
        }
    }

    protected function recordCheck(TrafficFine $fine, string $plate, $fineable, array $ticket): void
    {
        DB::table('fine_checks')->insert([
            'plate_number' => $ticket['plateNo'] ?? $plate,
            'checkable_type' => $fineable ? $fineable->getMorphClass() : null,
            'checkable_id' => $fineable ? $fineable->getKey() : null,
            'result' => 'fined',
            'ticket_count' => 1,
            'total_amount' => floatval($ticket['ticketAmount'] ?? 0),
            'response' => json_encode($ticket),
            'created_at' => now(),
        ]);
    }

    protected function signature(?string $plate, $date, float $amount): string
    {
        return sprintf('%s|%s|%.2f', $plate ?: '-', $date ? $date->toDateString() : '-', $amount);
    }

    protected function parseDate($value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function parseDateTime($value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Route a provider/plate-check failure into the shared auto-ticket stack
     * (point 7) so Support is the single inbox for fines connectivity issues.
     */
    protected function openFailureTicket(string $plate, $fineable, array $apiResult): void
    {
        $reason = $apiResult['message'] ?? ($apiResult['raw'] ?? 'unknown error');

        if (is_array($reason)) {
            $reason = json_encode($reason);
        }

        $this->autoTickets->open([
            'title' => "Fines check failed — {$plate}",
            'description' => "The fines provider did not return a usable result for plate {$plate}. Reason: {$reason}. The plate will be retried on the next scheduled sweep.",
            'priority' => SupportTicket::PRIORITY_HIGH,
            'source' => 'auto_fines',
        ], $fineable, 'Fines');
    }
}
