<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewTripRequest implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $trip;

    public function __construct(Trip $trip)
    {
        $this->trip = $trip;
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->trip->id,
            'origin_lat' => $this->trip->origin_lat,
            'origin_lng' => $this->trip->origin_lng,
            'origin_address' => $this->trip->origin_address,
            'destination_lat' => $this->trip->destination_lat,
            'destination_lng' => $this->trip->destination_lng,
            'destination_address' => $this->trip->destination_address,
            'distance' => $this->trip->distance,
            'passengers_count' => $this->trip->passengers_count,
            'created_at' => $this->trip->created_at,
        ];
    }

    public function broadcastOn()
    {
        // Assuming a simple 'drivers' channel for now, as user didn't specify zone logic yet.
        // In production this should probably be specific to a city or zone.
        return new PrivateChannel('drivers');
    }

    public function broadcastAs()
    {
        return 'NewTripRequest';
    }
}
