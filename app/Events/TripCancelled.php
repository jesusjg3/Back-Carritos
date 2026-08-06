<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripCancelled implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $trip;

    public function __construct(Trip $trip)
    {
        $this->trip = $trip;
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->trip->id,
            'message' => 'Trip cancelled',
            'trip' => [
                'id' => $this->trip->id,
                'cancel_reason' => $this->trip->cancel_reason
            ],
            'reason' => $this->trip->cancel_reason
        ];
    }

    public function broadcastOn()
    {
        $channels = [new PrivateChannel('drivers')];
        foreach ($this->trip->passengers as $passenger) {
            $channels[] = new PrivateChannel('passenger.' . $passenger->id);
        }
        return $channels;
    }

    public function broadcastAs()
    {
        return 'TripCancelled';
    }
}
