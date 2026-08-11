<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\User;
use App\Models\TripRating;
use App\Models\UserRating;
use App\Repositories\TripRatingRepository;
use App\Repositories\TripRepository;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

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
        ?string $comment = null,
        ?int $receiverId = null
    ): array {
        $trip = $this->tripRepo->find($tripId);

        if (!$trip) {
            throw new ModelNotFoundException("Carrera no encontrada");
        }

        // Determine receiver
        if ($fromUser->id === $trip->driver_id) {
            // Emisor es conductor, receptor es pasajero
            if (!$receiverId) {
                throw new \Exception("Debe especificar a qué pasajero calificar.");
            }
            $isPassenger = $trip->passengers->contains('id', $receiverId);
            if (!$isPassenger) {
                throw new \Exception("El usuario especificado no es pasajero de este viaje.");
            }
            $toUser = $this->userRepo->find($receiverId);
        } else {
            // Emisor es pasajero, receptor es conductor
            $isPassenger = $trip->passengers->contains('id', $fromUser->id);
            if (!$isPassenger) {
                throw new \Exception("El usuario no pertenece a esta carrera.");
            }
            if (!$trip->driver_id) {
                throw new \Exception("Esta carrera no tiene conductor asignado.");
            }
            $toUser = $this->userRepo->find($trip->driver_id);
        }

        if ($score < 1 || $score > 5) {
            throw new \Exception('Puntaje inválido');
        }

        return DB::transaction(function () use ($trip, $fromUser, $toUser, $score, $comment) {
            $rating = $this->tripRatingRepo->create([
                'trip_id' => $trip->id,
                'emitter_id' => $fromUser->id,
                'receiver_id' => $toUser->id,
                'rating' => $score,
                'comment' => $comment,
            ]);

            $ratingProfile = $this->userRepo->getRatingProfileForUpdate($toUser->id);

            $currentScore = (float) $ratingProfile->score;
            $currentCount = (int) $ratingProfile->rating_count;

            if ($currentCount === 0) {
                $newScore = (float) $score;
            } else {
                $newScore = (($currentScore * $currentCount) + $score) / ($currentCount + 1);
            }

            $this->userRepo->updateRatingProfile(
                $toUser->id, 
                round($newScore, 2), 
                $currentCount + 1
            );

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
        });
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



