<?php

namespace App\Events;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PassengerJoinRequested implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $trip;
    public $passenger;
    public $pickupData;

    public function __construct(Trip $trip, User $passenger, array $pickupData)
    {
        $this->trip = $trip;
        $this->passenger = $passenger;
        $this->pickupData = $pickupData;
    }

    public function broadcastWith()
    {
        return [
            'trip_id' => $this->trip->id,
            'passenger' => [
                'id' => $this->passenger->id,
                'name' => $this->passenger->name,
                'phone' => $this->passenger->phone,
                'pickup_lat' => $this->pickupData['origin_lat'] ?? null,
                'pickup_lng' => $this->pickupData['origin_lng'] ?? null,
                'pickup_address' => $this->pickupData['origin_address'] ?? null,
                'passengers_count' => $this->pickupData['passengers_count'] ?? 1
            ]
        ];
    }

    public function broadcastOn()
    {
        return new PrivateChannel('driver.' . $this->trip->driver_id);
    }

    public function broadcastAs()
    {
        return 'PassengerJoinRequested';
    }
}
