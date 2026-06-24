<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MechanicProfile extends Model
{
    protected $fillable = [
        'user_id',
        'specialization',
        'hourly_rate',
        'currency_code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
