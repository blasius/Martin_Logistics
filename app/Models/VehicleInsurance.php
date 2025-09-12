<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class VehicleInsurance extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'policy_number',
        'provider_name',
        'issue_date',
        'expiry_date',
        'document_path',
        'status',
    ];

    protected $dates = [
        'issue_date',
        'expiry_date',
    ];

    // Relationships
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    // Helpers
    public function isExpired(): bool
    {
        return Carbon::now()->greaterThan($this->expiry_date);
    }

    public function scopeActive($query)
    {
        return $query->where('expiry_date', '>=', Carbon::today());
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', Carbon::today());
    }
}
