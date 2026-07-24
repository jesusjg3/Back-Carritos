<?php

namespace App\Http\Controllers;

use App\Services\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DriverLocationController extends Controller
{
    private const ACTIVE_DRIVER_IDS_KEY = 'drivers.live.ids';
    protected LocationService $locationService;

    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    public function updateDriverLocation(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->rol || $user->rol->rol_name !== 'conductor') {
            return response()->json(['error' => 'Solo los conductores pueden actualizar su ubicación'], 403);
        }

        $data = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $driverId = $user->id;
        $payload = [
            'driver_id' => $driverId,
            'name' => $user->name,
            'latitude' => (float) $data['latitude'],
            'longitude' => (float) $data['longitude'],
            'updated_at' => now()->toIso8601String(),
        ];

        Cache::put($this->driverLocationKey($driverId), $payload, now()->addMinutes(30));
        Cache::put($this->driverOnlineKey($driverId), true, now()->addMinutes(30));
        $this->rememberDriverId($driverId);

        // Emitir evento para WebSockets
        broadcast(new \App\Events\DriverGlobalLocationUpdated($driverId, (float) $data['latitude'], (float) $data['longitude']));

        // Si el conductor tiene un viaje activo (aceptado o iniciado), emitir evento al pasajero
        $activeTrip = \App\Models\Trip::where('driver_id', $driverId)
            ->whereIn('state_id', [\App\Models\State::ACCEPTED, \App\Models\State::STARTED])
            ->first();

        if ($activeTrip) {
            $status = ($activeTrip->state_id === \App\Models\State::ACCEPTED) ? 'accepted' : 'started';
            broadcast(new \App\Events\TripLocationUpdated(
                $activeTrip->id,
                $driverId,
                (float) $data['latitude'],
                (float) $data['longitude'],
                $status
            ));
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
        broadcast(new \App\Events\DriverOffline($driverId));

        return response()->json(['message' => 'Conductor marcado como offline']);
    }

    public function getNearbyDrivers(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:0.1|max:500',
        ]);

        $centerLatitude = (float) $data['latitude'];
        $centerLongitude = (float) $data['longitude'];
        $radius = isset($data['radius']) ? (float) $data['radius'] : 10.0;

        $drivers = [];
        foreach ($this->activeDriverIds() as $driverId) {
            $location = Cache::get($this->driverLocationKey($driverId));

            if (!is_array($location)) {
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