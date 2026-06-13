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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
