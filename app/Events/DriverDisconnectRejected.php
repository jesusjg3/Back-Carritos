<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverDisconnectRejected implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $driverId;

    public function __construct(int $driverId)
    {
        $this->driverId = $driverId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('driver.' . $this->driverId);
    }

    public function broadcastAs()
    {
        return 'driver.disconnect.rejected';
    }
}
