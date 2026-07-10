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

    public function __construct(TripRepository $tripRepo, StatesService $statesService)
    {
        $this->tripRepo = $tripRepo;
        $this->statesService = $statesService;
    }

    public function getAllStates()
    {
        return $this->statesService->getAllStates();
    }

    public function getAllAdminTrips(int $perPage = 10)
    {
        return $this->tripRepo->getAllWithRelations($perPage);
    }

    /**
     * Usuario solicita carrera
     */
    public function requestTrip(array $data, User $user)
    {
        return DB::transaction(function () use ($data, $user) {

            $trip = $this->tripRepo->create(array_merge($data, [
                'passenger_id' => $user->id,
                'state_id' => State::REQUESTED,
            ]));

            $trip->load(['passenger', 'driver', 'state']);

            // Broadcast event to drivers
            broadcast(new \App\Events\NewTripRequest($trip));

            return $this->formatTripResponse($trip);
        });
    }
    /**
     * Conductor acepta carrera (with Race Condition protection)
     */
    public function acceptTripById(int $tripId, User $driver)
    {
        return DB::transaction(function () use ($tripId, $driver) {
            // 1. LOCK the row for update
            $trip = $this->tripRepo->findLocked($tripId);

            // 2. Critical Validation
            if ($trip->state_id !== State::REQUESTED || $trip->driver_id !== null) {
                // Return a specific structure or throw an exception that Controller catches as 409
                throw new \Exception('El viaje ya fue tomado por otro conductor.', 409);
            }

            // 3. Assign Driver
            $this->tripRepo->update($trip, [
                'driver_id' => $driver->id,
                'state_id' => State::ACCEPTED,
            ]);

            $trip->load(['passenger', 'driver', 'state']);

            // 4. Broadcast that trip is taken to drivers (remove from list)
            broadcast(new \App\Events\TripTaken($trip));

            // 5. Broadcast to specific passenger that trip is accepted
            broadcast(new \App\Events\TripAccepted($trip));

            return $this->formatTripResponse($trip);
        });
    }

    /**
     * Deprecated method kept for compatibility if needed, but redirects to new logic if possible
     * or acts as simple wrapper.Ideally should be removed or updated.
     */
    public function acceptTrip(Trip $trip, User $driver)
    {
        return $this->acceptTripById($trip->id, $driver);
    }

    /**
     * Iniciar carrera (Recoger al pasajero)
     */
    public function startTrip(Trip $trip, User $user)
    {
        if ($trip->driver_id !== $user->id) {
            throw new \Exception('No autorizado para iniciar este viaje.', 403);
        }

        // Idempotency: If already started, just return the trip
        if ($trip->state_id === State::STARTED) {
            $trip->load(['passenger', 'driver', 'state']);
            return $this->formatTripResponse($trip);
        }

        if ($trip->state_id !== State::ACCEPTED) {
            throw new \Exception('El viaje debe estar aceptado para iniciarse.', 400);
        }

        $trip = $this->tripRepo->update($trip, [
            'state_id' => State::STARTED,
        ]);

        $trip->load(['passenger', 'driver', 'state']);

        // Broadcast to passenger
        broadcast(new \App\Events\TripStarted($trip));

        return $this->formatTripResponse($trip);
    }

    /**
     * Finalizar carrera
     */
    public function finishTrip(Trip $trip, User $user)
    {
        if ($trip->driver_id !== $user->id) {
            throw new \Exception('No autorizado para finalizar este viaje.', 403);
        }

        $trip = $this->tripRepo->update($trip, [
            'state_id' => State::FINISHED,
        ]);

        $trip->load(['passenger', 'driver', 'state']);

        // Broadcast to passenger
        broadcast(new \App\Events\TripFinished($trip));

        return $this->formatTripResponse($trip);
    }

    /**
     * Cancelar carrera
     */
    public function cancelTrip(int $tripId, User $user)
    {
        return DB::transaction(function () use ($tripId, $user) {
            $trip = $this->tripRepo->findLocked($tripId);

            // Allow cancellation if not already finished or cancelled
            if ($trip->state_id === State::FINISHED || $trip->state_id === State::CANCELLED) {
                return $this->formatTripResponse($trip); // Already done
            }

            // Ensure rol relationship is loaded
            if (!$user->relationLoaded('rol')) {
                $user->load('rol');
            }
            $roleName = $user->rol->rol_name;

            // If user is passenger, verify ownership
            if ($roleName === 'pasajero' && $trip->passenger_id !== $user->id) {
                throw new \Exception('No autorizado para cancelar este viaje.', 403);
            }

            // If user is driver, verify assignment
            if ($roleName === 'conductor' && $trip->driver_id !== $user->id) {
                throw new \Exception('No autorizado para cancelar este viaje.', 403);
            }

            $currentStatus = $trip->state_id;

            $this->tripRepo->update($trip, [
                'state_id' => State::CANCELLED,
            ]);

            $trip->load(['passenger', 'driver', 'state']);

            // Broadcast Cancellation
            broadcast(new \App\Events\TripCancelled($trip));

            return $this->formatTripResponse($trip);
        });
    }

    private function formatTripResponse(Trip $trip): array
    {
        // Obtener ubicación del conductor desde el repositorio
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
            'passenger' => $trip->passenger ? [
                'name' => $trip->passenger->name
            ] : null,
            'driver' => $trip->driver ? [
                'id' => $trip->driver->id,
                'name' => $trip->driver->name,
                'rating' => $trip->driver->score ?? 5.0,
                'score' => $trip->driver->score ?? 5.0,
                'rating_count' => $trip->driver->rating_count ?? 0,
                // Coordenadas actuales del conductor (si existen)
                'latitude' => $driverLocation['latitude'] ?? null,
                'longitude' => $driverLocation['longitude'] ?? null,
                'last_update' => $driverLocation['last_update'] ?? null,
                // Estructura completa para compatibilidad
                'location' => $driverLocation,
            ] : null,
        ];
    }

    public function getTripHistory(User $user)
    {
        $trips = $this->tripRepo->getByPassenger($user->id);
        $trips->load(['driver', 'state']);

        // Eager load only the rating where emitter is the user
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



