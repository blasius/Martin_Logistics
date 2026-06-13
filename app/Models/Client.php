<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAuditTrail;

class Client extends Model
{
    use HasFactory, HasAuditTrail;
    protected $fillable = [
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
        'type',
        'tin',
    ];
}
