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
