<?php

namespace App\Services;

use App\Repositories\TripRepository;
use App\Models\Trip;
use App\Models\User;
use App\Models\State;
use Illuminate\Support\Facades\DB;

class TripService
{
    protected TripRepository $tripRepo;
    protected StatesService $statesService;
    protected ExpoPushService $pushService;

    public function __construct(TripRepository $tripRepo, StatesService $statesService, ExpoPushService $pushService)
    {
        $this->tripRepo = $tripRepo;
        $this->statesService = $statesService;
        $this->pushService = $pushService;
    }

    public function getAllStates()
    {
        return $this->statesService->getAllStates();
    }

    public function getAllAdminTrips(int $perPage = 10, ?string $search = null, ?string $stateName = null)
    {
        return $this->tripRepo->getAllWithRelations($perPage, $search, $stateName);
    }

    public function requestTrip(array $data, User $user)
    {
        return DB::transaction(function () use ($data, $user) {
            
            // Buscar viajes activos compartidos
            $activeTrips = $this->tripRepo->findActiveTripsForRoute($data['destination_address'], $data['passengers_count'] ?? 1);
            
            if ($activeTrips->isNotEmpty()) {
                $trip = $activeTrips->first();
                
                // Verificar si el usuario ya está en este viaje
                $existingPassenger = $this->tripRepo->getPassengerInTrip($trip->id, $user->id);

                if (!$existingPassenger) {
                    $this->tripRepo->addPassengerToTrip($trip->id, $user->id, 'requested');
                } else if ($existingPassenger->status === 'cancelled') {
                    $this->tripRepo->updatePassengerStatus($trip->id, $user->id, 'requested');
                }
                // Si ya está como 'requested', 'accepted', etc., no hacemos insert, 
                // simplemente reenviamos el evento para que los conductores lo vuelvan a ver.
                
                $trip->load(['passengers', 'driver', 'state']);
                
                broadcast(new \App\Events\NewTripRequest($trip));
                return $this->formatTripResponse($trip);
            }

            // Crear uno nuevo si no hay compatibles
            $trip = $this->tripRepo->create(array_merge($data, [
                'state_id' => State::REQUESTED,
            ]));
            
            $this->tripRepo->addPassengerToTrip($trip->id, $user->id, 'requested');

            $trip->load(['passengers', 'driver', 'state']);
            broadcast(new \App\Events\NewTripRequest($trip));

            return $this->formatTripResponse($trip);
        });
    }

    public function acceptTripById(int $tripId, User $driver, ?int $passengerId = null)
    {
        return DB::transaction(function () use ($tripId, $driver, $passengerId) {
            $trip = $this->tripRepo->findLocked($tripId);

            if ($trip->driver_id !== null && $trip->driver_id !== $driver->id) {
                throw new \Exception('El viaje ya fue tomado por otro conductor.', 409);
            }

            if ($trip->driver_id === null) {
                $this->tripRepo->update($trip, [
                    'driver_id' => $driver->id,
                    'state_id' => State::ACCEPTED,
                    'accepted_at' => now(),
                ]);
            }
            
            if ($passengerId) {
                $this->tripRepo->updatePassengerStatus($tripId, $passengerId, 'accepted');
            } else {
                $this->tripRepo->updateAllPassengersStatus($tripId, 'requested', 'accepted');
            }

            $trip->load(['passengers', 'driver', 'state']);

            broadcast(new \App\Events\TripTaken($trip));
            broadcast(new \App\Events\TripAccepted($trip));

            // Notify passengers
            foreach ($trip->passengers as $p) {
                $this->pushService->sendToUser(
                    $p->id,
                    "Viaje Aceptado",
                    "El conductor {$driver->name} ha aceptado tu viaje."
                );
            }

            return $this->formatTripResponse($trip);
        });
    }

    public function acceptTrip(Trip $trip, User $driver)
    {
        return $this->acceptTripById($trip->id, $driver);
    }

    public function startTrip(int $tripId, User $user)
    {
        $trip = $this->tripRepo->find($tripId);
        
        if ($trip->driver_id !== $user->id) {
            throw new \Exception('No autorizado para iniciar este viaje.', 403);
        }

        if ($trip->state_id === State::STARTED) {
            $trip->load(['passengers', 'driver', 'state']);
            return $this->formatTripResponse($trip);
        }

        $trip = $this->tripRepo->update($trip, [
            'state_id' => State::STARTED,
            'started_at' => now(),
        ]);

        $trip->load(['passengers', 'driver', 'state']);
        broadcast(new \App\Events\TripStarted($trip));

        // Notify passengers
        foreach ($trip->passengers as $p) {
            $this->pushService->sendToUser(
                $p->id,
                "Viaje Iniciado",
                "El conductor ha iniciado la ruta hacia el destino."
            );
        }

        return $this->formatTripResponse($trip);
    }
    
    public function boardPassenger(int $tripId, int $passengerId, User $driver)
    {
        $trip = $this->tripRepo->find($tripId);
        
        if ($trip->driver_id !== $driver->id) {
            throw new \Exception('No autorizado.', 403);
        }
        
        $this->tripRepo->updatePassengerStatus($trip->id, $passengerId, 'boarded');
            
        $trip->load(['passengers', 'driver', 'state']);
        return $this->formatTripResponse($trip);
    }

    public function dropOffPassenger(int $tripId, int $passengerId, User $driver)
    {
        $trip = $this->tripRepo->find($tripId);
        
        if ($trip->driver_id !== $driver->id) {
            throw new \Exception('No autorizado.', 403);
        }
        
        $this->tripRepo->updatePassengerStatus($trip->id, $passengerId, 'dropped_off');
            
        $trip->load(['passengers', 'driver', 'state']);
        return $this->formatTripResponse($trip);
    }

    public function finishTrip(int $tripId, User $user)
    {
        $trip = $this->tripRepo->find($tripId);
        
        if ($trip->driver_id !== $user->id) {
            throw new \Exception('No autorizado para finalizar este viaje.', 403);
        }

        $trip = $this->tripRepo->update($trip, [
            'state_id' => State::FINISHED,
            'finished_at' => now(),
        ]);
        
        $this->tripRepo->updateAllPassengersStatus($trip->id, 'boarded', 'dropped_off');

        $trip->load(['passengers', 'driver', 'state']);
        broadcast(new \App\Events\TripFinished($trip));

        foreach ($trip->passengers as $p) {
            $this->pushService->sendToUser(
                $p->id,
                "Viaje Finalizado",
                "Has llegado a tu destino. ¡Gracias por usar Carritos!"
            );
        }

        return $this->formatTripResponse($trip);
    }

    public function cancelTrip(int $tripId, User $user)
    {
        return DB::transaction(function () use ($tripId, $user) {
            $trip = $this->tripRepo->findLocked($tripId);

            if ($trip->state_id === State::FINISHED || $trip->state_id === State::CANCELLED) {
                return $this->formatTripResponse($trip);
            }

            if (!$user->relationLoaded('rol')) {
                $user->load('rol');
            }
            $roleName = $user->rol->rol_name;

            if ($roleName === 'pasajero') {
                $this->tripRepo->updatePassengerStatus($tripId, $user->id, 'cancelled');
                    
                $activePassengers = $this->tripRepo->getActivePassengersCount($tripId);
                    
                if ($activePassengers === 0) {
                    $this->tripRepo->update($trip, ['state_id' => State::CANCELLED]);
                }
            } else if ($roleName === 'conductor') {
                if ($trip->driver_id !== $user->id) {
                    throw new \Exception('No autorizado para cancelar este viaje.', 403);
                }
                
                $this->tripRepo->update($trip, ['state_id' => State::CANCELLED]);
                $this->tripRepo->cancelAllPassengers($tripId);
            }

            $trip->load(['passengers', 'driver', 'state']);
            broadcast(new \App\Events\TripCancelled($trip));

            if ($roleName === 'pasajero' && $trip->driver_id) {
                $this->pushService->sendToUser(
                    $trip->driver_id,
                    "Viaje Cancelado",
                    "Un pasajero ha cancelado su solicitud."
                );
            } else if ($roleName === 'conductor') {
                foreach ($trip->passengers as $p) {
                    $this->pushService->sendToUser(
                        $p->id,
                        "Viaje Cancelado",
                        "El conductor ha cancelado el viaje."
                    );
                }
            }

            return $this->formatTripResponse($trip);
        });
    }

    private function formatTripResponse(Trip $trip): array
    {
        $driverLocation = null;
        if ($trip->driver_id) {
            $driverLocation = $this->tripRepo->getDriverLocation($trip->driver_id);
        }

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
            'request_attempt' => $trip->request_attempt,
            'state_id' => $trip->state_id,
            'state' => $trip->state ? [
                'id' => $trip->state->id,
                'name' => $trip->state->state_name
            ] : null,
            'passengers' => $trip->passengers->map(function ($passenger) {
                return [
                    'id' => $passenger->id,
                    'name' => $passenger->name,
                    'phone' => $passenger->phone,
                    'status' => $passenger->pivot->status ?? 'requested',
                ];
            })->toArray(),
            'driver' => $trip->driver ? [
                'id' => $trip->driver->id,
                'name' => $trip->driver->name,
                'phone' => $trip->driver->phone,
                'rating' => $trip->driver->ratingProfile->score ?? 5.0,
                'score' => $trip->driver->ratingProfile->score ?? 5.0,
                'rating_count' => $trip->driver->ratingProfile->rating_count ?? 0,
                'latitude' => $driverLocation['latitude'] ?? null,
                'longitude' => $driverLocation['longitude'] ?? null,
                'last_update' => $driverLocation['last_update'] ?? null,
                'location' => $driverLocation,
            ] : null,
        ];
    }

    public function getTripHistory(User $user)
    {
        $trips = $this->tripRepo->getByPassenger($user->id);
        $trips->load(['driver', 'state']);

        $trips->load([
            'ratings' => function ($query) use ($user) {
                $query->where('emitter_id', $user->id);
            }
        ]);

        return $trips->map(function ($trip) {
            $myRating = $trip->ratings->first();
            return [
                'id' => $trip->id,
                'origin_address' => $trip->origin_address,
                'destination_address' => $trip->destination_address,
                'state' => $trip->state ? $trip->state->state_name : 'Unknown',
                'created_at' => $trip->created_at,
                'driver' => $trip->driver ? ['name' => $trip->driver->name] : null,
                'my_rating' => $myRating ? [
                    'rating' => $myRating->rating,
                    'comment' => $myRating->comment
                ] : null
            ];
        });
    }
}



