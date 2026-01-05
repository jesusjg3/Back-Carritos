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

    /**
     * Usuario solicita carrera
     */
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
            $trip = Trip::where('id', $tripId)->lockForUpdate()->firstOrFail();

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
    public function startTrip(Trip $trip)
    {
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
    public function finishTrip(Trip $trip)
    {
        $trip = $this->tripRepo->update($trip, [
            'state_id' => State::FINISHED,
        ]);

        $trip->load(['passenger', 'driver', 'state']);

        // Broadcast to passenger
        broadcast(new \App\Events\TripFinished($trip));

        return $this->formatTripResponse($trip);
    }

    private function formatTripResponse(Trip $trip): array
    {
        return [
            'id' => $trip->id,
            'origin_lat' => $trip->origin_lat,
            'origin_lng' => $trip->origin_lng,
            'origin_address' => $trip->origin_address,
            'destination_lat' => $trip->destination_lat,
            'destination_lng' => $trip->destination_lng,
            'destination_address' => $trip->destination_address,
            'distance' => $trip->distance,
            'state_id' => $trip->state_id, // Added for frontend compatibility
            'state' => $trip->state ? [
                'id' => $trip->state->id,
                'name' => $trip->state->state_name
            ] : null,
            'passenger' => $trip->passenger ? [
                'name' => $trip->passenger->name
            ] : null,
            'driver' => $trip->driver ? [
                'name' => $trip->driver->name
            ] : null,
        ];
    }
}



