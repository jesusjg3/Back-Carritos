<?php

namespace App\Services;

use App\Models\Career;
use App\Models\CareerPosition;
use App\Repositories\CareerPositionRepository;

class CareerPositionService
{
    protected CareerPositionRepository $careerPositionRepo;

    public function __construct(CareerPositionRepository $careerPositionRepo)
    {
        $this->careerPositionRepo = $careerPositionRepo;
    }

    public function storePosition(Career $career, float $lat, float $lng, string $type): CareerPosition
    {
        return $this->careerPositionRepo->create([
            'career_id' => $career->id,
            'lat' => $lat,
            'lng' => $lng,
            'type' => $type,
        ]);
    }
}
