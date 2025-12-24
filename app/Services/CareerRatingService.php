<?php

namespace App\Services;

use App\Models\Career;
use App\Models\User;
use App\Models\CareerRating;
use App\Repositories\CareerRatingRepository;

class CareerRatingService
{
    protected CareerRatingRepository $careerRatingRepo;

    public function __construct(CareerRatingRepository $careerRatingRepo)
    {
        $this->careerRatingRepo = $careerRatingRepo;
    }

    public function rate(
        Career $career,
        User $from,
        User $to,
        int $score,
        ?string $comment = null
    ): CareerRating {
        if ($score < 1 || $score > 5) {
            throw new \Exception('Puntaje inválido');
        }

        return $this->careerRatingRepo->create([
            'career_id' => $career->id,
            'emisor_id' => $from->id,
            'receptor_id' => $to->id,
            'puntaje' => $score,
            'comentario' => $comment,
        ]);
    }
}
