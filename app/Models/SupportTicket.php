<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{
    BelongsTo,
    HasMany,
    MorphTo
};

class SupportTicket extends Model
{
    protected $fillable = [
        'user_id',
        'support_category_id',
        'assigned_to',
        'title',
        'description',
        'priority',
        'status',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(SupportCategory::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(SupportTicketEvent::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
