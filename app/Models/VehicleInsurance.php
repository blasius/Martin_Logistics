<?php

namespace App\Models;

use Guava\Calendar\Contracts\Eventable;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class VehicleInsurance extends Model implements Eventable
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
        'issue_date' => 'date',
        'expiry_date' => 'date',
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

    /**
     * @return CalendarEvent
     */
    public function toCalendarEvent(): CalendarEvent
    {
        $today = Carbon::today();
        $expiryDate = Carbon::parse($this->expiry_date);

        $color = '#22c55e'; // Green by default (expiring after 30 days)

        if ($this->status === 'expired' || $expiryDate->isPast() || $expiryDate->isSameDay($today)) {
            // Red: Expired or expiring today
            $color = '#ef4444';
        } elseif ($expiryDate->isBetween($today->copy()->addDay(), $today->copy()->addDays(30))) {
            // Orange: Expiring between tomorrow and 30 days from now
            $color = '#f97316';
        } else {
            // Green: Expiring after 30 days
            $color = '#22c55e';
        }

        // Safe URL to the edit page
        $adminPath = config('filament.path', 'admin');
        $url = url("/{$adminPath}/resources/vehicle-insurances/{$this->id}/edit");

        // Get the vehicle plate number
        $title = $this->vehicle ? $this->vehicle->plate_number : $this->policy_number;

        return CalendarEvent::make($this)
            ->title($title)
            ->start($this->expiry_date)
            ->end($this->expiry_date)
            ->url($url)
            ->backgroundColor($color);
    }

    public function insurable()
    {
        return $this->morphTo();
    }
}
