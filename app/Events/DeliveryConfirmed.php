<?php

namespace App\Events;

use App\Models\ProofOfDelivery;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryConfirmed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ProofOfDelivery $proofOfDelivery;

    public function __construct(ProofOfDelivery $proofOfDelivery)
    {
        $this->proofOfDelivery = $proofOfDelivery;
    }
}
