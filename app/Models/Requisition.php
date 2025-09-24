<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Requisition extends Model
{
    protected $fillable = [
        'reference',
        'requester_id',
        'expense_type_id',
        'amount',
        'currency_id',
        'description',
        'related_trip_id',
        'status',
        'assigned_finance_user_id',
        'assigned_manager_user_id',
        'assigned_cashier_user_id',
        'voucher_number',
    ];

    protected static function booted()
    {
        static::creating(function ($m) {
            // Generate human readable reference if not provided
            if (empty($m->reference)) {
                $m->reference = 'REQ-' . now()->format('Ymd') . '-' . strtoupper(Str::substr(Str::uuid()->toString(), 0, 6));
            }
        });
    }

    // Relationships
    public function requester() { return $this->belongsTo(User::class, 'requester_id'); }
    public function expenseType() { return $this->belongsTo(ExpenseType::class, 'expense_type_id'); }
    public function proofs() { return $this->hasMany(RequisitionProof::class); }
    public function signatures() { return $this->hasMany(RequisitionSignature::class); }
    public function payment() { return $this->hasOne(Payment::class); }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public const STATUS_PENDING_FINANCE = 'pending_finance';
    public const STATUS_PENDING_MANAGEMENT = 'pending_management';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PENDING_PAYMENT = 'pending_payment'; // optional
    public const STATUS_PAID = 'paid';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ARCHIVED = 'archived';
}
