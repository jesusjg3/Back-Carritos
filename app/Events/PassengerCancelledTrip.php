<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PassengerCancelledTrip implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $trip;
    public $passengerId;

    public function __construct(Trip $trip, int $passengerId)
    {
        $this->trip = $trip;
        $this->passengerId = $passengerId;
    }

    public function broadcastWith()
    {
        return [
            'trip_id' => $this->trip->id,
            'passenger_id' => $this->passengerId,
            'trip' => $this->trip,
        ];
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel('driver.' . $this->trip->driver_id),
            new PrivateChannel('passenger.' . $this->passengerId)
        ];
    }

    public function broadcastAs()
    {
        return 'PassengerCancelledTrip';
    }
}
