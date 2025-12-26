<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\TripPosition;
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
        $this->TripPositionRepo = $tripPositionRepo;
        $this->tripRepo = $tripRepo;
    }

    public function storePosition(int $tripId, float $lat, float $lng, string $type): TripPosition
    {
        $trip = $this->tripRepo->find($tripId);

        if (!$trip) {
            throw new ModelNotFoundException("Carrera no encontrada");
        }

        return $this->TripPositionRepo->create([
            'Trip_id' => $trip->id,
            'lat' => $lat,
            'lng' => $lng,
            'type' => $type,
        ]);
    }
}



