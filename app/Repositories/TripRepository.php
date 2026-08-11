<?php

namespace App\Repositories;

use App\Models\Trip;
use App\Models\TripPassenger;
use App\Models\DriverLocation;
use App\Models\State;
use Illuminate\Support\Facades\DB;

class TripRepository
{
    public function __construct()
    {
    }

    public function all()
    {
        return Trip::all();
    }

    public function getAllWithRelations(int $perPage = 15, ?string $search = null, ?string $stateName = null)
    {
        $query = Trip::with([
            'passengers:users.id,users.name,users.email', 
            'driver:id,name,email', 
            'state:id,state_name', 
            'ratings:id,trip_id,emitter_id,receiver_id,rating,comment', 
            'ratings.emitter:id,name'
        ]);

        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                // Buscar por ID, direcciones, pasajero, conductor o estado del viaje
                $subQuery->where('id', 'like', "%{$search}%")
                    ->orWhere('origin_address', 'ilike', "%{$search}%")
                    ->orWhere('destination_address', 'ilike', "%{$search}%")
                    ->orWhereHas('passengers', function ($passengerQuery) use ($search) {
                        $passengerQuery->where('users.name', 'ilike', "%{$search}%");
                    })
                    ->orWhereHas('driver', function ($driverQuery) use ($search) {
                        $driverQuery->where('name', 'ilike', "%{$search}%");
                    })
                    ->orWhereHas('state', function ($stateQuery) use ($search) {
                        $stateQuery->where('state_name', 'ilike', "%{$search}%");
                    });
            });
        }

        if ($stateName) {
            $query->whereHas('state', function ($q) use ($stateName) {
                $q->where('state_name', $stateName);
            });
        }

        $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return $paginator;
    }

    public function find(int $id): Trip
    {
        return Trip::findOrFail($id);
    }

    public function getStats(?string $search = null): array
    {
        $baseCountQuery = Trip::query();
        if ($search) {
            $baseCountQuery->where(function ($subQuery) use ($search) {
                $subQuery->where('id', 'like', "%{$search}%")
                    ->orWhere('origin_address', 'ilike', "%{$search}%")
                    ->orWhere('destination_address', 'ilike', "%{$search}%");
            });
        }

        return [
            'total_terminados' => (clone $baseCountQuery)->whereHas('state', function($q){ $q->where('state_name', 'TERMINADO'); })->count(),
            'total_cancelados' => (clone $baseCountQuery)->whereHas('state', function($q){ $q->where('state_name', 'CANCELADO'); })->count(),
        ];
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
        return Trip::whereHas('passengers', function($q) use ($passengerId) {
            $q->where('users.id', $passengerId);
        })->orderBy('created_at', 'desc')->get();
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

    public function getDriverLocation(int $driverId): ?array
    {
        $location = \Illuminate\Support\Facades\Cache::get("driver.location.{$driverId}") ?? \Illuminate\Support\Facades\Cache::get("drivers.live.{$driverId}");
        
        // La clave actual que usa el LocationController es driverLocationKey($driverId)
        // que es driver.location.{id} o drivers.live.{id}
        
        if (is_array($location) && isset($location['latitude'])) {
            return $location;
        }
        
        return null;
    }

    public function findActiveTripsForRoute(string $destinationAddress, int $availableSeatsRequired = 1)
    {
        return Trip::whereIn('state_id', [State::REQUESTED, State::ACCEPTED, State::STARTED])
            ->where('destination_address', $destinationAddress)
            // Filtramos aquellos viajes donde el número de asientos ocupados más los que pide el nuevo no exceda un límite (ej. 4)
            ->whereRaw('COALESCE((SELECT COUNT(*) FROM trip_passengers tp WHERE tp.trip_id = trips.id AND tp.status NOT IN (\'cancelled\', \'dropped_off\')), 0) + ? <= 4', [$availableSeatsRequired])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function addPassengerToTrip(int $tripId, int $passengerId, string $status = 'requested')
    {
        return TripPassenger::create([
            'trip_id' => $tripId,
            'passenger_id' => $passengerId,
            'status' => $status
        ]);
    }

    public function getPassengerInTrip(int $tripId, int $passengerId)
    {
        return TripPassenger::where('trip_id', $tripId)
            ->where('passenger_id', $passengerId)
            ->first();
    }

    public function updatePassengerStatus(int $tripId, int $passengerId, string $status)
    {
        return DB::table('trip_passenger')
            ->where('trip_id', $tripId)
            ->where('passenger_id', $passengerId)
            ->update([
                'status' => $status,
                'updated_at' => now()
            ]);
    }

    public function updateAllPassengersStatus(int $tripId, string $oldStatus, string $newStatus)
    {
        return TripPassenger::where('trip_id', $tripId)
            ->where('status', $oldStatus)
            ->update(['status' => $newStatus]);
    }

    public function getActivePassengersCount(int $tripId)
    {
        return TripPassenger::where('trip_id', $tripId)
            ->whereIn('status', ['requested', 'accepted', 'boarded'])
            ->count();
    }

    public function cancelAllPassengers(int $tripId)
    {
        return TripPassenger::where('trip_id', $tripId)->update(['status' => 'cancelled']);
    }

    public function getActiveTripForDriver(int $driverId)
    {
        return Trip::where('driver_id', $driverId)
            ->whereIn('state_id', [State::ACCEPTED, State::STARTED])
            ->first();
    }
}

