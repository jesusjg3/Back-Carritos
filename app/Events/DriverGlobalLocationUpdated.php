<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
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

    /**
     * Create a new event instance.
     */
    public function __construct(int $driverId, float $latitude, float $longitude, string $vehicleStatus = 'active', ?string $name = null, ?array $vehicle = null, bool $isInEvent = false)
    {
        $this->driverId = $driverId;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->vehicleStatus = $vehicleStatus;
        $this->name = $name;
        $this->vehicle = $vehicle;
        $this->isInEvent = $isInEvent;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Siempre emitir al canal privado del administrador
        $channels = [
            new PrivateChannel('admin.live_tracking'),
        ];

        // Emitir al canal público solo si no está en un evento
        if (!$this->isInEvent) {
            $channels[] = new Channel('drivers.live');
        }

        return $channels;
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
