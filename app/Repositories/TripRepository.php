<?php

namespace App\Repositories;

use App\Models\Trip;
use App\Repositories\DriverLocationRepository;

class TripRepository
{
    protected DriverLocationRepository $driverLocationRepo;

    public function __construct(DriverLocationRepository $driverLocationRepo)
    {
        $this->driverLocationRepo = $driverLocationRepo;
    }

    public function all()
    {
        return Trip::all();
    }

    public function getAllWithRelations(int $perPage = 15, ?string $search = null)
    {
        $query = Trip::with(['passenger', 'driver', 'state', 'ratings', 'ratings.emitter']);

        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                // Buscar por ID, direcciones, pasajero, conductor o estado del viaje
                $subQuery->where('id', 'like', "%{$search}%")
                    ->orWhere('origin_address', 'ilike', "%{$search}%")
                    ->orWhere('destination_address', 'ilike', "%{$search}%")
                    ->orWhereHas('passenger', function ($passengerQuery) use ($search) {
                        $passengerQuery->where('name', 'ilike', "%{$search}%");
                    })
                    ->orWhereHas('driver', function ($driverQuery) use ($search) {
                        $driverQuery->where('name', 'ilike', "%{$search}%");
                    })
                    ->orWhereHas('state', function ($stateQuery) use ($search) {
                        $stateQuery->where('state_name', 'ilike', "%{$search}%");
                    });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function find(int $id): Trip
    {
        return Trip::findOrFail($id);
    }

    public function create(array $data): Trip
    {
        return Trip::create($data);
    }

    public function getByDriver(int $driverId)
    {
        return Trip::where('driver_id', $driverId)->get();
    }

    public function getByPassenger(int $passengerId)
    {
        return Trip::where('passenger_id', $passengerId)->orderBy('created_at', 'desc')->get();
    }

    public function findLocked(int $id): Trip
    {
        return Trip::where('id', $id)->lockForUpdate()->firstOrFail();
    }

    public function update(Trip $trip, array $data): Trip
    {
        $trip->update($data);
        return $trip;
    }

    public function delete(Trip $trip): bool
    {
        return $trip->delete();
    }

    /**
     * Obtener ubicación del conductor para un viaje
     * Delega al DriverLocationRepository
     */
    public function getDriverLocation(int $driverId): ?array
    {
        return $this->driverLocationRepo->getFormattedLocation($driverId);
    }
}

