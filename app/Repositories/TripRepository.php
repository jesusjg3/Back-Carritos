<?php

namespace App\Repositories;

use App\Models\Trip;
use App\Models\TripPassenger;
use App\Models\State;
use App\Models\DriverLocation;
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


        $counts = (clone $baseCountQuery)
            ->join('states', 'trips.state_id', '=', 'states.id')
            ->selectRaw("
                COUNT(*) as total_registrados,
                COUNT(CASE WHEN states.state_name = 'TERMINADO' THEN 1 END) as total_terminados,
                COUNT(CASE WHEN states.state_name = 'CANCELADO' THEN 1 END) as total_cancelados
            ")->first();

        return [
            'total_registrados' => (int) ($counts->total_registrados ?? 0),
            'total_terminados' => (int) ($counts->total_terminados ?? 0),
            'total_cancelados' => (int) ($counts->total_cancelados ?? 0),
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

    public function findActiveTripsForRoute(string $destinationAddress, int $availableSeatsRequired = 1, ?int $excludePassengerId = null)
    {
        $query = Trip::select('trips.*')
            ->join('assignments', function ($join) {
                $join->on('trips.driver_id', '=', 'assignments.user_id')
                     ->where('assignments.is_active', true);
            })
            ->join('vehicles', 'assignments.vehicle_id', '=', 'vehicles.id')
            ->whereIn('trips.state_id', [State::STARTED])
            ->where('trips.destination_address', $destinationAddress)
            // Filtramos aquellos viajes donde el número de asientos ocupados más los que pide el nuevo no exceda la capacidad del vehículo
            ->whereRaw('COALESCE((SELECT SUM(passengers_count) FROM trip_passengers tp WHERE tp.trip_id = trips.id AND tp.status NOT IN (\'cancelled\', \'dropped_off\')), 0) + ? <= vehicles.capacity', [$availableSeatsRequired])
            ->orderBy('trips.created_at', 'desc')
            ->lockForUpdate();

        if ($excludePassengerId) {
            $query->whereNotIn('trips.id', function($q) use ($excludePassengerId) {
                $q->select('trip_id')
                  ->from('trip_passengers')
                  ->where('passenger_id', $excludePassengerId)
                  ->where('status', 'cancelled');
            });
        }

        return $query->get();
    }

    public function addPassengerToTrip(int $tripId, int $passengerId, string $status = 'requested', array $pickupData = [])
    {
        return TripPassenger::create([
            'trip_id' => $tripId,
            'passenger_id' => $passengerId,
            'status' => $status,
            'pickup_lat' => $pickupData['pickup_lat'] ?? null,
            'pickup_lng' => $pickupData['pickup_lng'] ?? null,
            'pickup_address' => $pickupData['pickup_address'] ?? null,
            'passengers_count' => $pickupData['passengers_count'] ?? 1,
        ]);
    }

    public function getPassengerInTrip(int $tripId, int $passengerId)
    {
        return TripPassenger::where('trip_id', $tripId)
            ->where('passenger_id', $passengerId)
            ->first();
    }

    public function getCurrentActiveTripForUser(int $userId)
    {
        // Priorizar si es pasajero
        $passengerTrip = Trip::select('trips.*')
            ->join('trip_passengers', 'trips.id', '=', 'trip_passengers.trip_id')
            ->where('trip_passengers.passenger_id', $userId)
            ->whereIn('trip_passengers.status', [
                'requested',
                'accepted',
                'boarded'
            ])
            ->whereIn('trips.state_id', [State::REQUESTED, State::ACCEPTED, State::STARTED])
            ->first();

        if ($passengerTrip) {
            return $passengerTrip;
        }

        // Si no es pasajero activo, buscar si es conductor activo
        return Trip::where('driver_id', $userId)
            ->whereIn('state_id', [State::REQUESTED, State::ACCEPTED, State::STARTED])
            ->first();
    }

    public function updatePassengerStatus(int $tripId, int $passengerId, string $status, array $pickupData = [])
    {
        $data = [
            'status' => $status,
            'updated_at' => now()
        ];

        if (array_key_exists('pickup_lat', $pickupData)) {
            $data['pickup_lat'] = $pickupData['pickup_lat'];
        }
        if (array_key_exists('pickup_lng', $pickupData)) {
            $data['pickup_lng'] = $pickupData['pickup_lng'];
        }
        if (array_key_exists('pickup_address', $pickupData)) {
            $data['pickup_address'] = $pickupData['pickup_address'];
        }
        if (array_key_exists('passengers_count', $pickupData)) {
            $data['passengers_count'] = $pickupData['passengers_count'];
        }

        return DB::table('trip_passengers')
            ->where('trip_id', $tripId)
            ->where('passenger_id', $passengerId)
            ->update($data);
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

    public function getBusyDriverIds(): array
    {
        return Trip::whereIn('state_id', [State::ACCEPTED, State::STARTED])
            ->whereNotNull('driver_id')
            ->pluck('driver_id')
            ->toArray();
    }
}

