<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use App\Traits\HasAuditTrail;

class TrafficFine extends Model
{
    use HasAuditTrail;

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PAID = 'PAID';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_DISPUTED = 'DISPUTED';

    /**
     * Statuses that no longer represent money owed. Anything outside this list
     * is treated as outstanding debt by every query in the app.
     */
    public const SETTLED_STATUSES = [self::STATUS_PAID, self::STATUS_CANCELLED, self::STATUS_DISPUTED];

    /**
     * Due-state buckets, ordered by urgency. Derived exclusively from the
     * portal's `payBy` date — the app never assumes a payment window length,
     * because Irembo issues 12 to 33 day windows depending on the offence.
     */
    public const DUE_STATE_OVERDUE = 'overdue';
    public const DUE_STATE_DUE_SOON = 'due_soon';
    public const DUE_STATE_DUE = 'due';
    public const DUE_STATE_SETTLED = 'settled';
    public const DUE_STATE_UNKNOWN = 'unknown';

    /**
     * Window used to raise an amber "due soon" warning ahead of the red
     * overdue state. Presentation only — never used to decide debt.
     */
    public const DUE_SOON_DAYS = 3;

    /**
     * Column holding the date the offence actually happened.
     *
     * This must never be substituted with `created_at`. created_at records
     * when *we* imported the ticket from the police portal, which routinely
     * lags the offence by weeks or months (observed up to 750 days), and it
     * collapses into a handful of sync-batch days. Anything measuring "how
     * often are we getting fined" has to use issued_at.
     */
    public const ISSUED_DATE_COLUMN = 'issued_at';

    protected $auditExcluded = ['raw'];
    protected $fillable = [
        'ticket_number','ticket_amount','late_fee','paid_amount','issued_at','pay_by',
        'status','paid_at','payment_reference','payment_method','payment_status',
        'plate_number','location','raw'
    ];

    protected $casts = [
        'ticket_amount' => 'decimal:2',
        'late_fee' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'issued_at' => 'date',
        'pay_by' => 'date',
        'paid_at' => 'datetime',
        'raw' => 'array',
    ];

    protected $appends = ['total_due', 'due_state', 'days_until_pay_by'];

    public function fineable()
    {
        return $this->morphTo();
    }

    public function violations()
    {
        return $this->hasMany(TrafficFineViolation::class);
    }

    /* ---------------------------------------------------------------------
     | Scopes
     |--------------------------------------------------------------------*/

    /**
     * Fines that still represent money owed. This is the canonical
     * definition of "unpaid" used across the app.
     */
    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('status')
                ->orWhereNotIn('status', self::SETTLED_STATUSES);
        });
    }

    public function scopeSettled(Builder $query): Builder
    {
        return $query->whereIn('status', self::SETTLED_STATUSES);
    }

    /**
     * Outstanding fines whose portal Pay By date has passed.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->outstanding()
            ->whereNotNull('pay_by')
            ->whereDate('pay_by', '<', now());
    }

    /**
     * Outstanding fines falling due within the next $days days (inclusive of
     * today, exclusive of overdue).
     */
    public function scopeDueWithin(Builder $query, int $days = self::DUE_SOON_DAYS): Builder
    {
        return $query->outstanding()
            ->whereNotNull('pay_by')
            ->whereDate('pay_by', '>=', now())
            ->whereDate('pay_by', '<=', now()->addDays($days));
    }

    /**
     * Fines the portal has already attached a penalty to.
     */
    public function scopePenalized(Builder $query): Builder
    {
        return $query->outstanding()->where('late_fee', '>', 0);
    }

    /* ---------------------------------------------------------------------
     | Issuance date (offence date, not import date)
     |--------------------------------------------------------------------*/

    /**
     * Fines issued by the police portal within a date range. This is the
     * correct basis for any frequency question such as "how often do we get
     * fined" — grouping by created_at reports our sync schedule, not our
     * offending rate.
     */
    public function scopeIssuedBetween(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween(self::ISSUED_DATE_COLUMN, [$from, $to]);
    }

    /**
     * Fines issued by the police portal on a single calendar day.
     */
    public function scopeIssuedOn(Builder $query, $date): Builder
    {
        return $query->whereDate(self::ISSUED_DATE_COLUMN, Carbon::parse($date)->toDateString());
    }

    /* ---------------------------------------------------------------------
     | State
     |--------------------------------------------------------------------*/

    /**
     * Principal owed, excluding any late fee.
     */
    public function getPrincipalAttribute(): float
    {
        return (float) $this->ticket_amount;
    }

    /**
     * Total amount owed on an outstanding fine: principal + late fee.
     * Settled fines always owe nothing.
     */
    public function getTotalDueAttribute(): float
    {
        if ($this->isSettled()) {
            return 0.0;
        }

        return round((float) $this->ticket_amount + (float) $this->late_fee, 2);
    }

    public function isSettled(): bool
    {
        return $this->status !== null
            && in_array($this->status, self::SETTLED_STATUSES, true);
    }

    public function isOverdue(): bool
    {
        return $this->dueState() === self::DUE_STATE_OVERDUE;
    }

    /**
     * Days until the portal Pay By date. Negative once overdue, null when the
     * portal did not supply a Pay By date.
     */
    public function daysUntilPayBy(): ?int
    {
        if (!$this->pay_by) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->pay_by->startOfDay(), false);
    }

    /**
     * How many days past the Pay By date this fine sits, 0 when not overdue.
     */
    public function daysOverdue(): int
    {
        $days = $this->daysUntilPayBy();

        return $days !== null && $days < 0 ? abs($days) : 0;
    }

    /**
     * Urgency bucket derived from the portal Pay By date.
     */
    public function dueState(): string
    {
        if ($this->isSettled()) {
            return self::DUE_STATE_SETTLED;
        }

        if (!$this->pay_by) {
            return self::DUE_STATE_UNKNOWN;
        }

        $days = $this->daysUntilPayBy();

        if ($days < 0) {
            return self::DUE_STATE_OVERDUE;
        }

        if ($days <= self::DUE_SOON_DAYS) {
            return self::DUE_STATE_DUE_SOON;
        }

        return self::DUE_STATE_DUE;
    }

    /**
     * Human label for the due state, used in exports and CSV.
     */
    public function dueStateLabel(): string
    {
        return match ($this->dueState()) {
            self::DUE_STATE_OVERDUE => 'OVERDUE',
            self::DUE_STATE_DUE_SOON => 'DUE SOON',
            self::DUE_STATE_DUE => 'DUE',
            self::DUE_STATE_SETTLED => 'SETTLED',
            default => 'UNKNOWN',
        };
    }

    public function getDueStateAttribute(): string
    {
        return $this->dueState();
    }

    public function getDaysUntilPayByAttribute(): ?int
    {
        return $this->daysUntilPayBy();
    }

    /* ---------------------------------------------------------------------
     | Settlement
     |--------------------------------------------------------------------*/

    /**
     * Mark the fine as paid for the full outstanding amount.
     *
     * Idempotent: a fine that is already settled keeps its original
     * paid_at / paid_amount so history is never rewritten.
     */
    public function settleAsPaid(?Carbon $paidAt = null, ?float $paidAmount = null): bool
    {
        if ($this->isSettled()) {
            return false;
        }

        // Capture the amount owed before flipping the status: once settled,
        // total_due is defined as zero.
        $owed = $paidAmount ?? $this->total_due;

        $this->status = self::STATUS_PAID;
        $this->paid_at = $this->paid_at ?? ($paidAt ?? now());
        $this->paid_amount = $this->paid_amount > 0
            ? $this->paid_amount
            : round((float) $owed, 2);

        return $this->save();
    }
}