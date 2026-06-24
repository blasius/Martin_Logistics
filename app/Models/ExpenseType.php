<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseType extends Model
{
    protected $fillable = [
        'name',
        'description',
        'category',
        'expense_class',
        'default_amount',
        'currency_id',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
