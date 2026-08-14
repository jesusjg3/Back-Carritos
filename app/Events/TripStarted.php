<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Repositories\TripRepository;

class TripStarted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $trip;

    /**
     * Create a new event instance.
     */
    public function __construct(Trip $trip)
    {
        $this->trip = $trip;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];
        foreach ($this->trip->passengers as $passenger) {
            $channels[] = new PrivateChannel('passenger.' . $passenger->id);
        }
        return $channels;
    }

    public function broadcastWith(): array
    {
        $this->trip->load(['state']);

        return [
            'trip' => [
                'id' => $this->trip->id,
                'state_id' => $this->trip->state_id,
                'state_name' => $this->trip->state->state_name ?? null,
                'driver' => $this->trip->driver ? [
                    'id' => $this->trip->driver->id,
                    'name' => $this->trip->driver->name,
                    'email' => $this->trip->driver->email,
                    'latitude' => app(TripRepository::class)->getDriverLocation($this->trip->driver_id)['latitude'] ?? null,
                    'longitude' => app(TripRepository::class)->getDriverLocation($this->trip->driver_id)['longitude'] ?? null,
                ] : null,
                'origin' => [
                    'lat' => $this->trip->origin_lat,
                    'lng' => $this->trip->origin_lng,
                    'address' => $this->trip->origin_address
                ],
                'destination' => [
                    'lat' => $this->trip->destination_lat,
                    'lng' => $this->trip->destination_lng,
                    'address' => $this->trip->destination_address
                ],
                'passengers' => $this->trip->passengers->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'phone' => $p->phone,
                        'status' => $p->pivot->status,
                        'pickup_lat' => $p->pivot->pickup_lat,
                        'pickup_lng' => $p->pivot->pickup_lng,
                        'pickup_address' => $p->pivot->pickup_address,
                        'passengers_count' => $p->pivot->passengers_count,
                    ];
                })
            ]
        ];
    }

    public function broadcastAs()
    {
        return 'TripStarted';
    }
}
