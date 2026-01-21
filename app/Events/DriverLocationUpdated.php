<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $driverId;
    public int $tripId;
    public float $latitude;
    public float $longitude;

    /**
     * Create a new event instance.
     */
    public function __construct(int $driverId, int $tripId, float $latitude, float $longitude)
    {
        $this->driverId = $driverId;
        $this->tripId = $tripId;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Emitir al canal privado del viaje específico
        return [
            new PrivateChannel('trip.' . $this->tripId),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'driver_id' => $this->driverId,
            'trip_id' => $this->tripId,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'DriverLocationUpdated';
    }
}
