<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'type', 'value', 'verified_at',
        'is_primary', 'verification_code', 'code_expires_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'code_expires_at' => 'datetime',
        'is_primary' => 'boolean',
    ];

    protected $dates = ['verified_at', 'code_expires_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }
}
