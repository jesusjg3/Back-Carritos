<?php

namespace App\Services;

use App\Repositories\DriverLocationRepository;
use App\Models\User;
use App\Models\DriverLocation;
use App\Models\Trip;
use App\Models\State;
use App\Events\DriverGlobalLocationUpdated;
use App\Events\TripLocationUpdated;

class DriverLocationService
{
    protected DriverLocationRepository $locationRepo;
    protected TripRepository $tripRepo;

    public function __construct(DriverLocationRepository $locationRepo, TripRepository $tripRepo)
    {
        $this->locationRepo = $locationRepo;
        $this->tripRepo = $tripRepo;
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
        $location = $this->locationRepo->updateOrCreateLocation($user->id, [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'is_online' => true,
            'last_update' => now(),
        ]);

        // Emitir siempre este evento público para que pasajeros IDLE y admin vean moverse el coche
        broadcast(new DriverGlobalLocationUpdated(
            $user->id,
            $latitude,
            $longitude
        ));

        // Si el conductor tiene un viaje activo (aceptado o iniciado), emitir evento al pasajero
        $activeTrip = $this->tripRepo->getActiveTripForDriver($user->id);

        if ($activeTrip) {
            $status = ($activeTrip->state_id === State::ACCEPTED) ? 'accepted' : 'started';

            broadcast(new TripLocationUpdated(
                $activeTrip->id,
                $user->id,
                $latitude,
                $longitude,
                $status
            ));
        }

        return $location;
    }

    /**
     * Obtiene conductores cercanos
     */
    public function getNearbyDrivers(float $latitude, float $longitude, ?float $radius = null)
    {
        $radius = $radius ?? (float) env('DRIVER_SEARCH_RADIUS_KM', 5);
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
