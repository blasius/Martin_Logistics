<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAuditTrail;

class Client extends Model
{
    use HasFactory, HasAuditTrail;

    protected $fillable = [
        'user_id',
        'contact_person',
        'phone',
        'address',
        'type',
        'tin',
    ];

    protected $appends = ['name', 'email'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function getNameAttribute(): ?string
    {
        return $this->user?->name;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->user?->email;
    }
}
