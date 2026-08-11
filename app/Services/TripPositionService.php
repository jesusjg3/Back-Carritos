<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\TripPosition;
use App\Models\User;
use App\Repositories\TripPositionRepository;
use App\Repositories\TripRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TripPositionService
{
    protected TripPositionRepository $tripPositionRepo;
    protected TripRepository $tripRepo;

    public function __construct(
        TripPositionRepository $tripPositionRepo,
        TripRepository $tripRepo
    ) {
        $this->tripPositionRepo = $tripPositionRepo;
        $this->tripRepo = $tripRepo;
    }

    public function storePosition(int $tripId, User $user, float $lat, float $lng, string $type): TripPosition
    {
        $trip = $this->tripRepo->find($tripId);



        if ($trip->driver_id !== $user->id) {
            throw new \Exception('No autorizado para enviar posición de esta carrera.', 403);
        }

        return $this->tripPositionRepo->create([
            'trip_id' => $trip->id,
            'lat' => $lat,
            'lng' => $lng,
            'type' => $type,
        ]);
    }
}



