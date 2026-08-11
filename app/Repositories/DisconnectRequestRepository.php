<?php

namespace App\Repositories;

use App\Models\DisconnectRequest;
use Illuminate\Database\Eloquent\Collection;

class DisconnectRequestRepository
{
    /**
     * Get the pending disconnect request for a driver, if any.
     */
    public function getPendingRequestForDriver(int $driverId): ?DisconnectRequest
    {
        return DisconnectRequest::where('driver_id', $driverId)
            ->where('status', 'pending')
            ->first();
    }

    /**
     * Check if a driver has a pending disconnect request.
     */
    public function hasPendingRequest(int $driverId): bool
    {
        return DisconnectRequest::where('driver_id', $driverId)
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * Create or update a pending disconnect request for a driver.
     */
    public function createPendingRequest(int $driverId, string $reason): DisconnectRequest
    {
        return DisconnectRequest::updateOrCreate(
            ['driver_id' => $driverId, 'status' => 'pending'],
            ['reason' => $reason]
        );
    }

    /**
     * Update the status of a disconnect request.
     */
    public function updateStatus(int $driverId, string $status): bool
    {
        return DisconnectRequest::where('driver_id', $driverId)
            ->where('status', 'pending')
            ->update(['status' => $status]) > 0;
    }

    /**
     * Get all drivers with pending requests.
     */
    public function getPendingDriverIds(): array
    {
        return DisconnectRequest::where('status', 'pending')
            ->pluck('driver_id')
            ->toArray();
    }
}
