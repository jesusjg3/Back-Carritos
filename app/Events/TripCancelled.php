<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripCancelled implements ShouldBroadcastNow
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
            'message' => 'Trip cancelled'
        ];
    }

    public function broadcastOn()
    {
        // Broadcast to 'drivers' to remove from request list
        // And 'passenger.{id}' to notify passenger (if cancelled by driver/system, though here it's usually passenger cancelling)
        // If passenger cancels, drivers need to know.
        // If driver cancels, passenger needs to know.
        // For simplicity, we broadcast to 'drivers' and the specific passenger channel.
        return [
            new PrivateChannel('drivers'),
            new PrivateChannel('passenger.' . $this->trip->passenger_id)
        ];
    }

    public function broadcastAs()
    {
        return 'TripCancelled';
    }
}
