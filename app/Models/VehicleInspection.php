<?php

namespace App\Models;

use Carbon\Carbon;
use Guava\Calendar\Contracts\Eventable;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Illuminate\Database\Eloquent\Model;

class VehicleInspection extends Model implements Eventable
{
    protected $fillable = [
        'vehicle_id',
        'scheduled_date',
        'completed_date',
        'inspector_name',
        'document_path',
        'status',
        'replaces_id',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function replaces()
    {
        return $this->belongsTo(VehicleInspection::class, 'replaces_id');
    }

    public function replacedBy()
    {
        return $this->hasOne(VehicleInspection::class, 'replaces_id');
    }

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_date' => 'date',
    ];

    public function toCalendarEvent(): CalendarEvent
    {
        $today = Carbon::today();
        $scheduledDate = Carbon::parse($this->scheduled_date);

        $color = '#22c55e'; // Default to green

        // Check for completed status first, as this should override date-based coloring
        if ($scheduledDate->isPast() || $scheduledDate->isSameDay($today)) {
            // Red: Scheduled date is in the past or today
            $color = '#ef4444';
        } elseif ($scheduledDate->isBetween($today->copy()->addDay(), $today->copy()->addDays(30))) {
            // Orange: Scheduled date is between tomorrow and 30 days from now
            $color = '#f97316';
        } else {
            // Green: Scheduled date is after 30 days
            $color = '#22c55e';
        }

        return CalendarEvent::make($this)
            ->title($this->vehicle->plate_number)
            ->start($this->scheduled_date)
            ->end($this->scheduled_date)
            ->backgroundColor($color);
    }

    public function inspected()
    {
        return $this->morphTo();
    }

}

