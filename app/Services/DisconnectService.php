<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\DisconnectRequestRepository;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Events\DriverDisconnectRequested;
use App\Events\DriverDisconnectApproved;
use App\Events\DriverDisconnectRejected;

class DisconnectService
{
    public function __construct(
        protected DisconnectRequestRepository $disconnectRequestRepository,
        protected LocationService $locationService
    ) {}

    /**
     * Handle a driver's request to disconnect.
     */
    public function requestDisconnect(User $user, string $reason): void
    {
        $this->disconnectRequestRepository->createPendingRequest($user->id, $reason);

        try {
            broadcast(new DriverDisconnectRequested($user, $reason));
        } catch (Exception $e) {
            Log::error("Error broadcasting disconnect request: " . $e->getMessage());
            throw new Exception("Error al solicitar desconexión.");
        }
    }

    /**
     * Approve a driver's pending disconnect request.
     */
    public function approveDisconnect(int $driverId): void
    {
        $driverUser = User::find($driverId);
        // No es posible revocar tokens JWT específicos desde el lado del administrador sin el token exacto
        // o sin una tabla de lista negra, por lo que simplemente lo marcamos offline.

        $this->disconnectRequestRepository->updateStatus($driverId, 'approved');
        
        // Remove from active drivers / location
        $this->locationService->setDriverOffline($driverId);

        try {
            broadcast(new DriverDisconnectApproved($driverId));
        } catch (Exception $e) {
            Log::error("Error broadcasting disconnect approved: " . $e->getMessage());
        }
    }

    /**
     * Reject a driver's pending disconnect request.
     */
    public function rejectDisconnect(int $driverId): void
    {
        $this->disconnectRequestRepository->updateStatus($driverId, 'rejected');

        try {
            broadcast(new DriverDisconnectRejected($driverId));
        } catch (Exception $e) {
            Log::error("Error broadcasting disconnect rejected: " . $e->getMessage());
        }
    }

    /**
     * Check if a driver has a pending request.
     */
    public function hasPendingRequest(int $driverId): bool
    {
        return $this->disconnectRequestRepository->hasPendingRequest($driverId);
    }

    public function getPendingDriverIds(): array
    {
        return $this->disconnectRequestRepository->getPendingDriverIds();
    }

    /**
     * Get paginated disconnect requests.
     */
    public function getPaginatedRequests(int $perPage = 10, ?string $status = null)
    {
        return $this->disconnectRequestRepository->getPaginatedRequests($perPage, $status);
    }

    /**
     * Get global stats for disconnect requests.
     */
    public function getGlobalStats(): array
    {
        return $this->disconnectRequestRepository->getGlobalStats();
    }
}
