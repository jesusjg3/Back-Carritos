<?php

namespace App\Services;

use App\Repositories\DriverLocationRepository;
use App\Models\User;
use App\Models\DriverLocation;

class DriverLocationService
{
    protected DriverLocationRepository $locationRepo;

    public function __construct(DriverLocationRepository $locationRepo)
    {
        $this->locationRepo = $locationRepo;
    }

    /**
     * Actualiza o crea la ubicación del conductor
     */
    public function updateLocation(User $user, float $latitude, float $longitude): DriverLocation
    {
        // Validar que sea conductor
        if ($user->rol->rol_name !== 'conductor') {
            throw new \Exception('Solo los conductores pueden actualizar su ubicación', 403);
        }

        // Delegar al repositorio
        return $this->locationRepo->updateOrCreateLocation($user->id, [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'is_online' => true,
            'last_update' => now(),
        ]);
    }

    /**
     * Obtiene conductores cercanos
     */
    public function getNearbyDrivers(float $latitude, float $longitude, float $radius = 5)
    {
        // Obtener colección del repositorio
        $locations = $this->locationRepo->getNearbyOnlineDrivers($latitude, $longitude, $radius);

        // Transformar datos para la respuesta (DTO o Array simple)
        return $locations->map(function ($location) {
            return [
                'id' => $location->user->id,
                'name' => $location->user->name,
                'lat' => (float) $location->latitude,
                'lng' => (float) $location->longitude,
                'distance' => round($location->distance, 2),
                'last_update' => $location->last_update,
            ];
        });
    }
    public function setOffline(User $user): void
    {
        if ($user->rol->rol_name !== 'conductor') {
            return; // O lanzar excepción
        }
        $this->locationRepo->setOffline($user->id);
    }
}
