<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\User;
use App\Models\TripRating;
use App\Repositories\TripRatingRepository;
use App\Repositories\TripRepository;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TripRatingService
{
    protected TripRatingRepository $tripRatingRepo;
    protected TripRepository $tripRepo;
    protected UserRepository $userRepo;

    public function __construct(
        TripRatingRepository $tripRatingRepo,
        TripRepository $tripRepo,
        UserRepository $userRepo
    ) {
        $this->tripRatingRepo = $tripRatingRepo;
        $this->tripRepo = $tripRepo;
        $this->userRepo = $userRepo;
    }

    public function rate(
        int $tripId,
        User $fromUser,
        int $score,
        ?string $comment = null
    ): array {
        $trip = $this->tripRepo->find($tripId);

        if (!$trip) {
            throw new ModelNotFoundException("Carrera no encontrada");
        }

        // Determine receiver
        if ($fromUser->id === $trip->passenger_id) {
            // Emisor es pasajero, receptor es conductor
            if (!$trip->driver_id) {
                throw new \Exception("Esta carrera no tiene conductor asignado.");
            }
            $toUser = $this->userRepo->find($trip->driver_id);
        } elseif ($fromUser->id === $trip->driver_id) {
            // Emisor es conductor, receptor es pasajero
            $toUser = $this->userRepo->find($trip->passenger_id);
        } else {
            throw new \Exception("El usuario no pertenece a esta carrera.");
        }

        if ($score < 1 || $score > 5) {
            throw new \Exception('Puntaje inválido');
        }

        $rating = $this->tripRatingRepo->create([
            'trip_id' => $trip->id,
            'emitter_id' => $fromUser->id,
            'receiver_id' => $toUser->id,
            'rating' => $score,
            'comment' => $comment,
        ]);

        return [
            'id' => $rating->id,
            'trip_id' => $rating->trip_id,
            'rating' => $rating->rating,
            'comment' => $rating->comment,
            'emitter' => [
                'name' => $fromUser->name,
            ],
            'receiver' => [
                'name' => $toUser->name,
            ],
            'created_at' => $rating->created_at,
        ];
    }

    public function getRatingsReceived(User $user): array
    {
        $ratings = $this->tripRatingRepo->getReceivedOneByUser($user->id);

        return $ratings->map(function ($rating) {
            return [
                'id' => $rating->id,
                'trip_id' => $rating->trip_id,
                'rating' => $rating->rating,
                'comment' => $rating->comment,
                'created_at' => $rating->created_at,
                'emitter' => $rating->emitter ? [
                    'name' => $rating->emitter->name,
                ] : null,
            ];
        })->toArray();
    }
}



