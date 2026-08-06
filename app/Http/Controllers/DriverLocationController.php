<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDriverLocationRequest;
use App\Http\Requests\GetNearbyDriversRequest;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Events\DriverGlobalLocationUpdated;
use App\Events\TripLocationUpdated;
use App\Events\DriverOffline;
use App\Events\DriverDisconnectRequested;
use App\Events\DriverDisconnectApproved;
use App\Events\DriverDisconnectRejected;
use App\Models\Trip;
use App\Models\State;

class DriverLocationController extends Controller
{
    private const ACTIVE_DRIVER_IDS_KEY = 'drivers.live.ids';
    protected LocationService $locationService;

    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    public function updateDriverLocation(UpdateDriverLocationRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->rol || $user->rol->rol_name !== 'conductor') {
            return response()->json(['error' => 'Solo los conductores pueden actualizar su ubicación'], 403);
        }

        $data = $request->validated();

        $driverId = $user->id;
        $driverProfile = \App\Models\DriverProfile::where('user_id', $driverId)->with('vehicle')->first();
        $vehicle = $driverProfile ? $driverProfile->vehicle : null;

        $payload = [
            'driver_id' => $driverId,
            'name' => $user->name,
            'latitude' => (float) $data['latitude'],
            'longitude' => (float) $data['longitude'],
            'updated_at' => now()->toIso8601String(),
            'vehicle' => $vehicle ? [
                'plate' => $vehicle->plate,
                'brand' => $vehicle->brand,
                'status' => $vehicle->status,
            ] : null,
        ];

        Cache::put($this->driverLocationKey($driverId), $payload, now()->addMinutes(30));
        Cache::put($this->driverOnlineKey($driverId), true, now()->addMinutes(30));
        $this->rememberDriverId($driverId);

        // Emitir evento para WebSockets
        try {
            $vStatus = $vehicle ? $vehicle->status : 'active';
            broadcast(new DriverGlobalLocationUpdated($driverId, (float) $data['latitude'], (float) $data['longitude'], $vStatus));
        } catch (Exception $e) {
            Log::error("Error broadcasting DriverGlobalLocationUpdated: " . $e->getMessage());
        }

        // Si el conductor tiene un viaje activo (aceptado o iniciado), emitir evento al pasajero
        $activeTrip = Trip::where('driver_id', $driverId)
            ->whereIn('state_id', [State::ACCEPTED, State::STARTED])
            ->first();

        if ($activeTrip) {
            $status = ($activeTrip->state_id === State::ACCEPTED) ? 'accepted' : 'started';
            try {
                broadcast(new TripLocationUpdated(
                    $activeTrip->id,
                    $driverId,
                    (float) $data['latitude'],
                    (float) $data['longitude'],
                    $status
                ));
            } catch (Exception $e) {
                Log::error("Error broadcasting TripLocationUpdated: " . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Ubicación actualizada',
            'driver' => $payload,
        ]);
    }

    public function setDriverOffline(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->rol || $user->rol->rol_name !== 'conductor') {
            return response()->json(['error' => 'Solo los conductores pueden cambiar su estado'], 403);
        }

        $driverId = $user->id;
        Cache::forget($this->driverLocationKey($driverId));
        Cache::forget($this->driverOnlineKey($driverId));
        $this->forgetDriverId($driverId);

        // Emitir evento para desconectar al conductor inmediatamente
        try {
            broadcast(new DriverOffline($driverId));
        } catch (Exception $e) {
            Log::error("Error broadcasting DriverOffline: " . $e->getMessage());
        }

        return response()->json(['message' => 'Conductor marcado como offline']);
    }

    public function requestDisconnect(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->rol || $user->rol->rol_name !== 'conductor') {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $data = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        try {
            broadcast(new DriverDisconnectRequested($user, $data['reason']));
        } catch (Exception $e) {
            Log::error("Error broadcasting DriverDisconnectRequested: " . $e->getMessage());
        }

        return response()->json(['message' => 'Solicitud de desconexión enviada al administrador.']);
    }

    public function approveDisconnect(int $driverId): JsonResponse
    {
        Cache::forget($this->driverLocationKey($driverId));
        Cache::forget($this->driverOnlineKey($driverId));
        $this->forgetDriverId($driverId);

        try {
            broadcast(new DriverOffline($driverId));
            broadcast(new DriverDisconnectApproved($driverId));
        } catch (Exception $e) {
            Log::error("Error broadcasting disconnect approved: " . $e->getMessage());
        }

        return response()->json(['message' => 'Desconexión del conductor aprobada.']);
    }

    public function rejectDisconnect(int $driverId): JsonResponse
    {
        try {
            broadcast(new DriverDisconnectRejected($driverId));
        } catch (Exception $e) {
            Log::error("Error broadcasting disconnect rejected: " . $e->getMessage());
        }

        return response()->json(['message' => 'Desconexión del conductor rechazada.']);
    }

    public function getNearbyDrivers(GetNearbyDriversRequest $request): JsonResponse
    {
        $data = $request->validated();

        $centerLatitude = (float) $data['latitude'];
        $centerLongitude = (float) $data['longitude'];
        $radius = isset($data['radius']) ? (float) $data['radius'] : 10.0;

        $drivers = [];
        foreach ($this->activeDriverIds() as $driverId) {
            $location = Cache::get($this->driverLocationKey($driverId));

            if (!is_array($location)) {
                continue;
            }

            // Skip drivers whose vehicle is in maintenance
            if (isset($location['vehicle']) && $location['vehicle']['status'] === 'maintenance') {
                continue;
            }

            $distance = $this->locationService->calculateDistanceKm(
                $centerLatitude,
                $centerLongitude,
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

        return response()->json(['drivers' => $drivers]);
    }

    public function getOnlineDrivers(): JsonResponse
    {
        $drivers = [];
        foreach ($this->activeDriverIds() as $driverId) {
            $location = Cache::get($this->driverLocationKey($driverId));
            $isOnline = Cache::get($this->driverOnlineKey($driverId), false);

            if (is_array($location) && $isOnline) {
                $drivers[] = [
                    'id' => $location['driver_id'],
                    'name' => $location['name'],
                    'latitude' => (float) $location['latitude'],
                    'longitude' => (float) $location['longitude'],
                    'location_updated_at' => $location['updated_at'],
                    'is_online' => true,
                    'vehicle' => $location['vehicle'] ?? null,
                ];
            }
        }

        return response()->json($drivers);
    }

    private function driverLocationKey(int $driverId): string
    {
        return "driver.location.{$driverId}";
    }

    private function driverOnlineKey(int $driverId): string
    {
        return "driver.online.{$driverId}";
    }

    private function activeDriverIds(): array
    {
        return Cache::get(self::ACTIVE_DRIVER_IDS_KEY, []);
    }

    private function rememberDriverId(int $driverId): void
    {
        $driverIds = $this->activeDriverIds();

        if (!in_array($driverId, $driverIds, true)) {
            $driverIds[] = $driverId;
            Cache::put(self::ACTIVE_DRIVER_IDS_KEY, $driverIds, now()->addMinutes(30));
        }
    }

    private function forgetDriverId(int $driverId): void
    {
        $driverIds = array_values(array_filter(
            $this->activeDriverIds(),
            static fn (int $existingId) => $existingId !== $driverId
        ));

        Cache::put(self::ACTIVE_DRIVER_IDS_KEY, $driverIds, now()->addMinutes(30));
    }
}