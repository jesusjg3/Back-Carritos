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

