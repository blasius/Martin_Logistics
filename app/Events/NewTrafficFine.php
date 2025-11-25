<?php

namespace App\Events;

use App\Models\TrafficFine;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewTrafficFine implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $fine;

    public function __construct(TrafficFine $fine)
    {
        // send only minimal payload needed by dashboard
        $this->fine = [
            'id' => $fine->id,
            'plate_number' => $fine->plate_number,
            'ticket_amount' => (float)$fine->ticket_amount,
            'status' => $fine->status,
            'created_at' => $fine->created_at->toDateTimeString(),
        ];
    }

    public function broadcastOn(): Channel
    {
        // public channel or private/channel as you prefer
        return new Channel('traffic-fines');
    }

    public function broadcastAs(): string
    {
        return 'NewTrafficFine';
    }
}
