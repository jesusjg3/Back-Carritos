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

    public function getPendingDriverIds(): array
    {
        return DisconnectRequest::where('status', 'pending')
            ->pluck('driver_id')
            ->toArray();
    }

    /**
     * Get paginated disconnect requests.
     */
    public function getPaginatedRequests(int $perPage = 10, ?string $status = null)
    {
        $query = DisconnectRequest::with(['driver' => function ($query) {
            $query->select('id', 'name', 'email'); // only fetch important fields
        }])->select('id', 'driver_id', 'reason', 'status', 'created_at', 'updated_at')
          ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get global stats for disconnect requests.
     */
    public function getGlobalStats(): array
    {
        return [
            'total_aprobados' => DisconnectRequest::where('status', 'approved')->count(),
            'total_rechazados' => DisconnectRequest::where('status', 'rejected')->count(),
        ];
    }
}
