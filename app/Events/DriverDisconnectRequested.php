<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class DriverDisconnectRequested implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $driverId;
    public $driverName;
    public $reason;

    public function __construct(User $driver, string $reason)
    {
        $this->driverId = $driver->id;
        $this->driverName = $driver->name;
        $this->reason = $reason;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('admin.notifications');
    }

    public function broadcastAs()
    {
        return 'driver.disconnect.requested';
    }
}
