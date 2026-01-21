<?php

namespace App\Repositories;

use App\Models\DriverLocation;
use Illuminate\Database\Eloquent\Collection;

class DriverLocationRepository
{
    /**
     * Actualizar o crear la ubicación de un conductor
     */
    public function updateOrCreateLocation(int $userId, array $data): DriverLocation
    {
        return DriverLocation::updateOrCreate(
            ['user_id' => $userId],
            $data
        );
    }

    /**
     * Obtener la ubicación de un conductor por user_id
     */
    public function getLocationByUserId(int $userId): ?DriverLocation
    {
        return DriverLocation::where('user_id', $userId)->first();
    }

    /**
     * Obtener ubicación formateada para respuesta API
     */
    public function getFormattedLocation(int $userId): ?array
    {
        $location = $this->getLocationByUserId($userId);

        if (!$location) {
            return null;
        }

        return [
            'latitude' => (float) $location->latitude,
            'longitude' => (float) $location->longitude,
            'last_update' => $location->last_update,
        ];
    }

    /**
     * Obtener conductores cercanos y online
     */
    public function getNearbyOnlineDrivers(float $latitude, float $longitude, float $radius): Collection
    {
        return DriverLocation::with('user')
            ->online() // Scope del modelo
            ->nearby($latitude, $longitude, $radius) // Scope del modelo
            ->get();
    }

    public function setOffline(int $userId): void
    {
        DriverLocation::where('user_id', $userId)->update(['is_online' => false]);
    }
}
