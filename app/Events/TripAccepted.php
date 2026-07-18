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

class TripAccepted implements ShouldBroadcastNow
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
        // Cargar relaciones necesarias para el frontend
        $this->trip->load(['state']);

        return [
            'trip' => [
                'id' => $this->trip->id,
                'state_id' => $this->trip->state_id,
                'driver' => $this->trip->driver ? [
                    'id' => $this->trip->driver->id,
                    'name' => $this->trip->driver->name,
                    'email' => $this->trip->driver->email,
                    'rating' => $this->trip->driver->ratingProfile->score ?? 5.0,
                    'score' => $this->trip->driver->ratingProfile->score ?? 5.0,
                    'latitude' => \Illuminate\Support\Facades\Cache::get("driver.location.{$this->trip->driver_id}")['latitude'] ?? null,
                    'longitude' => \Illuminate\Support\Facades\Cache::get("driver.location.{$this->trip->driver_id}")['longitude'] ?? null,
                    // Agregar más campos si es necesario (foto, placa, etc.)
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
                ]
            ]
        ];
    }

    public function broadcastAs()
    {
        return 'TripAccepted';
    }
}
