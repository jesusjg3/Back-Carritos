<?php

namespace App\Repositories;

use App\Models\Trip;
use App\Models\TripPassenger;

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

        $paginator->getCollection()->transform(function ($trip) {
            return [
                'id' => $trip->id,
                'origin_lat' => $trip->origin_lat,
                'origin_lng' => $trip->origin_lng,
                'origin_address' => $trip->origin_address,
                'destination_lat' => $trip->destination_lat,
                'destination_lng' => $trip->destination_lng,
                'destination_address' => $trip->destination_address,
                'distance' => $trip->distance,
                'passengers_count' => $trip->passengers_count,
                'state_id' => $trip->state_id,
                'created_at' => $trip->created_at,
                'updated_at' => $trip->updated_at,
                'state' => $trip->state ? ['state_name' => $trip->state->state_name] : null,
                'driver' => $trip->driver ? ['name' => $trip->driver->name] : null,
                'passengers' => $trip->passengers->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'pivot' => ['status' => $p->pivot->status]
                    ];
                })->toArray(),
                'ratings' => $trip->ratings->map(function ($r) {
                    return [
                        'id' => $r->id,
                        'rating' => $r->rating,
                        'comment' => $r->comment,
                        'emitter' => $r->emitter ? ['name' => $r->emitter->name] : null
                    ];
                })->toArray(),
            ];
        });

        return $paginator;
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
        $location = \Illuminate\Support\Facades\Cache::get("driver.location.{$driverId}");
        if (is_array($location) && isset($location['latitude'])) {
            return $location;
        }
        
        $dbLocation = \App\Models\DriverLocation::where('user_id', $driverId)->first();
        if ($dbLocation) {
            return [
                'latitude' => (float) $dbLocation->latitude,
                'longitude' => (float) $dbLocation->longitude,
                'last_update' => $dbLocation->last_update
            ];
        }
        return null;
    }

    public function findActiveTripsForRoute(string $destinationAddress, int $availableSeatsRequired = 1)
    {
        return Trip::whereIn('state_id', [\App\Models\State::REQUESTED, \App\Models\State::ACCEPTED, \App\Models\State::STARTED])
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
        return TripPassenger::where('trip_id', $tripId)
            ->where('passenger_id', $passengerId)
            ->update(['status' => $status]);
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
            ->whereIn('state_id', [\App\Models\State::ACCEPTED, \App\Models\State::STARTED])
            ->first();
    }
}

