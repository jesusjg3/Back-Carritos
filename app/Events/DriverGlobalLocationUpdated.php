<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
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
    public ?string $name;
    public ?array $vehicle;
    public bool $isInEvent;
    public bool $isAvailable;

    /**
     * Create a new event instance.
     */
    public function __construct(int $driverId, float $latitude, float $longitude, string $vehicleStatus = 'active', ?string $name = null, ?array $vehicle = null, bool $isInEvent = false, bool $isAvailable = true)
    {
        $this->driverId = $driverId;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->vehicleStatus = $vehicleStatus;
        $this->name = $name;
        $this->vehicle = $vehicle;
        $this->isInEvent = $isInEvent;
        $this->isAvailable = $isAvailable;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.live_tracking'),
            new PrivateChannel('drivers.live'),
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
            'name' => $this->name,
            'vehicle' => $this->vehicle,
            'is_in_event' => $this->isInEvent,
            'is_available' => $this->isAvailable,
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
