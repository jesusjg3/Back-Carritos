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
    public function requestTrip(array $data, User $user)
    {
        return DB::transaction(function () use ($data, $user) {

            $trip = $this->tripRepo->create(array_merge($data, [
                'passenger_id' => $user->id,
                'state_id' => State::REQUESTED,
            ]));

            $trip->load(['passenger', 'driver', 'state']);
            return $this->formatTripResponse($trip);
        });
    }

    /**
     * Conductor acepta carrera
     */
    public function acceptTrip(Trip $trip, User $driver)
    {
        if ($trip->state_id !== State::REQUESTED) {
            throw new \Exception('La carrera no está disponible');
        }

        $trip = $this->tripRepo->update($trip, [
            'driver_id' => $driver->id,
            'state_id' => State::ACCEPTED,
        ]);

        $trip->load(['passenger', 'driver', 'state']);
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
        return $this->formatTripResponse($trip);
    }

    private function formatTripResponse(Trip $trip): array
    {
        return [
            'id' => $trip->id,
            'origin_lat' => $trip->origin_lat,
            'origin_lng' => $trip->origin_lng,
            'destination_lat' => $trip->destination_lat,
            'destination_lng' => $trip->destination_lng,
            'distance' => $trip->distance,
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



