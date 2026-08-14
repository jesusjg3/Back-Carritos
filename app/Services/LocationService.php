<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use App\Models\Trip;
use App\Models\State;
use App\Events\DriverGlobalLocationUpdated;
use App\Events\TripLocationUpdated;
use App\Events\DriverOffline;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Repositories\EventRepository;
use App\Repositories\TripRepository;
use App\Repositories\VehicleRepository;
use App\Repositories\DisconnectRequestRepository;
use App\Repositories\AssignmentRepository;

class LocationService
{
    private const ACTIVE_DRIVER_IDS_KEY = 'drivers.live.ids';
    protected EventRepository $eventRepo;
    protected TripRepository $tripRepo;
    protected DisconnectRequestRepository $disconnectRequestRepository;
    protected AssignmentRepository $assignmentRepository;

    public function __construct(
        EventRepository $eventRepo,
        TripRepository $tripRepo,
        DisconnectRequestRepository $disconnectRequestRepository,
        AssignmentRepository $assignmentRepository
    ) {
        $this->eventRepo = $eventRepo;
        $this->tripRepo = $tripRepo;
        $this->disconnectRequestRepository = $disconnectRequestRepository;
        $this->assignmentRepository = $assignmentRepository;
    }

    public function calculateDistanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371;

        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($deltaLng / 2) ** 2;

        return 2 * $earthRadiusKm * asin(min(1, sqrt($a)));
    }

    public function getActiveEventVehicleIds(): array
    {
        return Cache::remember('active_event_vehicles', 60, function () {
            return $this->eventRepo->getActiveEventVehicleIds();
        });
    }

    public function getDriverLocationKey(int $driverId): string
    {
        return "driver.location.{$driverId}";
    }

    public function getDriverOnlineKey(int $driverId): string
    {
        return "driver.online.{$driverId}";
    }

    public function getActiveDriverIds(): array
    {
        return Cache::get(self::ACTIVE_DRIVER_IDS_KEY, []);
    }

    public function rememberDriverId(int $driverId): void
    {
        $driverIds = $this->getActiveDriverIds();

        if (!in_array($driverId, $driverIds, true)) {
            $driverIds[] = $driverId;
            Cache::put(self::ACTIVE_DRIVER_IDS_KEY, $driverIds, now()->addMinutes(30));
        }
    }

    public function forgetDriverId(int $driverId): void
    {
        $driverIds = $this->getActiveDriverIds();

        $driverIds = array_filter($driverIds, static fn($id) => $id !== $driverId);

        Cache::put(self::ACTIVE_DRIVER_IDS_KEY, $driverIds, now()->addMinutes(30));
    }

    public function updateLocationAndBroadcast(int $driverId, string $driverName, array $data): ?array
    {
        if ($this->disconnectRequestRepository->hasPendingRequest($driverId)) {
            // Ignorar actualizaciones si hay una desconexión pendiente
            return null;
        }

        $assignment = $this->assignmentRepository->getActiveAssignmentByUserId($driverId);
        $vehicle = $assignment ? $assignment->vehicle : null;

        $payload = [
            'driver_id' => $driverId,
            'name' => $driverName,
            'latitude' => (float) $data['latitude'],
            'longitude' => (float) $data['longitude'],
            'updated_at' => now()->toIso8601String(),
            'vehicle' => $vehicle ? [
                'id' => $vehicle->id,
                'plate' => $vehicle->plate,
                'brand' => $vehicle->brand,
                'status' => $vehicle->status,
            ] : null,
        ];

        Cache::put($this->getDriverLocationKey($driverId), $payload, now()->addMinutes(30));
        Cache::put($this->getDriverOnlineKey($driverId), true, now()->addMinutes(30));
        $this->rememberDriverId($driverId);

        // Remove from approved disconnects if they reconnect
        // This is no longer needed since they are forcefully logged out.

        // Check if vehicle is in an active event
        $isInEvent = false;
        if ($vehicle) {
            $activeEventVehicleIds = $this->getActiveEventVehicleIds();
            $isInEvent = in_array($vehicle->id, $activeEventVehicleIds);
        }

        try {
            $vStatus = $vehicle ? $vehicle->status : 'active';
            broadcast(new DriverGlobalLocationUpdated($driverId, (float) $data['latitude'], (float) $data['longitude'], $vStatus, $driverName, $payload['vehicle'], $isInEvent));
        } catch (Exception $e) {
            Log::error("Error broadcasting DriverGlobalLocationUpdated: " . $e->getMessage());
        }

        $this->broadcastToActiveTrip($driverId, (float) $data['latitude'], (float) $data['longitude']);

        return $payload;
    }

    public function setDriverOffline(int $driverId): void
    {
        Cache::forget($this->getDriverLocationKey($driverId));
        Cache::forget($this->getDriverOnlineKey($driverId));
        $this->forgetDriverId($driverId);

        try {
            broadcast(new DriverOffline($driverId));
        } catch (Exception $e) {
            Log::error("Error broadcasting DriverOffline: " . $e->getMessage());
        }
    }

    private function broadcastToActiveTrip(int $driverId, float $lat, float $lng): void
    {
        $activeTrip = $this->tripRepo->getActiveTripForDriver($driverId);

        if ($activeTrip) {
            $status = ($activeTrip->state_id === State::ACCEPTED) ? 'accepted' : 'started';
            try {
                broadcast(new TripLocationUpdated(
                    $activeTrip->id,
                    $driverId,
                    $lat,
                    $lng,
                    $status
                ));
            } catch (Exception $e) {
                Log::error("Error broadcasting TripLocationUpdated: " . $e->getMessage());
            }
        }
    }

    public function getNearbyDrivers(float $centerLat, float $centerLng, float $radius): array
    {
        $activeEventVehicleIds = $this->getActiveEventVehicleIds();

        // Obtener conductores que actualmente tienen un viaje activo (para no mostrarlos a usuarios genéricos)
        $busyDriverIds = $this->tripRepo->getBusyDriverIds();

        $drivers = [];
        foreach ($this->getActiveDriverIds() as $driverId) {
            if (in_array($driverId, $busyDriverIds)) {
                continue;
            }

            $location = Cache::get($this->getDriverLocationKey($driverId));

            if (!is_array($location)) {
                continue;
            }

            if (isset($location['vehicle']) && $location['vehicle']) {
                if ($location['vehicle']['status'] === 'maintenance') {
                    continue;
                }
                $vId = $location['vehicle']['id'] ?? null;
                if ($vId && in_array($vId, $activeEventVehicleIds)) {
                    continue;
                }
            }

            $distance = $this->calculateDistanceKm(
                $centerLat,
                $centerLng,
                (float) $location['latitude'],
                (float) $location['longitude']
            );

            if ($distance > $radius) {
                continue;
            }

            $drivers[] = [
                'id' => $location['driver_id'],
                'name' => $location['name'],
                'lat' => (float) $location['latitude'],
                'lng' => (float) $location['longitude'],
                'distance_km' => round($distance, 3),
                'updated_at' => $location['updated_at'],
            ];
        }

        usort($drivers, static fn (array $left, array $right) => $left['distance_km'] <=> $right['distance_km']);

        return $drivers;
    }

    public function getOnlineDrivers(bool $isAdmin): array
    {
        $activeEventVehicleIds = $isAdmin ? [] : $this->getActiveEventVehicleIds();
        $pendingDriverIds = $isAdmin ? $this->disconnectRequestRepository->getPendingDriverIds() : [];

        $drivers = [];
        foreach ($this->getActiveDriverIds() as $driverId) {
            $location = Cache::get($this->getDriverLocationKey($driverId));
            $isOnline = Cache::get($this->getDriverOnlineKey($driverId), false);

            if (is_array($location) && $isOnline) {
                if (!$isAdmin && isset($location['vehicle']) && $location['vehicle']) {
                    $vId = $location['vehicle']['id'] ?? null;
                    if ($vId && in_array($vId, $activeEventVehicleIds)) {
                        continue;
                    }
                }

                $drivers[] = [
                    'id' => $location['driver_id'],
                    'name' => $location['name'],
                    'latitude' => (float) $location['latitude'],
                    'longitude' => (float) $location['longitude'],
                    'location_updated_at' => $location['updated_at'],
                    'is_online' => true,
                    'vehicle' => $location['vehicle'] ?? null,
                    'is_disconnect_pending' => in_array($driverId, $pendingDriverIds),
                ];
            }
        }

        return $drivers;
    }
}
