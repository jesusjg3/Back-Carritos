<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverGlobalLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $driverId;
    public float $latitude;
    public float $longitude;
    public string $vehicleStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(int $driverId, float $latitude, float $longitude, string $vehicleStatus = 'active')
    {
        $this->driverId = $driverId;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->vehicleStatus = $vehicleStatus;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Emitir a un canal PÚBLICO para monitoreo global
        return [
            new Channel('drivers.live'),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'driver_id' => $this->driverId,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'vehicle_status' => $this->vehicleStatus,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'DriverGlobalLocationUpdated';
    }
}
