<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DashboardStatsUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $stats;
    public $hourly;

    public function __construct(array $stats, array $hourly)
    {
        $this->stats = $stats;
        $this->hourly = $hourly;
    }

    public function broadcastOn()
    {
        return new Channel('dashboard.stats');
    }

    public function broadcastAs()
    {
        return 'DashboardStatsUpdated'; // Laravel automatically prefixes with namespace unless we do this, or Reverb handles it. Wait, by default Laravel removes the namespace. But returning a string forces the exact event name.
    }
}
