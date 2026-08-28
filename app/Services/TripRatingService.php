<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\User;
use App\Models\TripRating;
use App\Models\UserRating;
use App\Models\State;
use App\Models\TripPassenger;
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

        if ($trip->state_id !== State::FINISHED) {
            throw new \Exception('Solo se pueden calificar viajes finalizados.');
        }

        // Determine receiver(s). A conductor can submit one evaluation for the
        // whole shared trip; the same score/comment is applied to every
        // passenger who was actually dropped off.
        if ($fromUser->id === $trip->driver_id) {
            if ($receiverId) {
                $receiver = $trip->passengers->firstWhere('id', $receiverId);
                if (!$receiver || $receiver->pivot->status !== TripPassenger::STATUS_DROPPED_OFF) {
                    throw new \Exception("El usuario especificado no es pasajero de este viaje.");
                }
                $receivers = collect([$receiver]);
            } else {
                $receivers = $trip->passengers->filter(
                    fn ($passenger) => $passenger->pivot?->status === TripPassenger::STATUS_DROPPED_OFF
                );

                if ($receivers->isEmpty()) {
                    throw new \Exception("No hay pasajeros finalizados para calificar.");
                }
            }
        } else {
            // Emisor es pasajero, receptor es conductor
            $passenger = $trip->passengers->firstWhere('id', $fromUser->id);
            if (!$passenger || $passenger->pivot->status !== TripPassenger::STATUS_DROPPED_OFF) {
                throw new \Exception("El usuario no pertenece a esta carrera.");
            }
            if (!$trip->driver_id) {
                throw new \Exception("Esta carrera no tiene conductor asignado.");
            }
            $receivers = collect([$this->userRepo->find($trip->driver_id)]);
        }

        if ($score < 1 || $score > 5) {
            throw new \Exception('Puntaje inválido');
        }

        return DB::transaction(function () use ($trip, $fromUser, $receivers, $score, $comment) {
            $ratings = [];

            foreach ($receivers as $toUser) {
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
                $newScore = $currentCount === 0
                    ? (float) $score
                    : (($currentScore * $currentCount) + $score) / ($currentCount + 1);

                $this->userRepo->updateRatingProfile(
                    $toUser->id,
                    round($newScore, 2),
                    $currentCount + 1
                );

                $ratings[] = [
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

            return [
                ...$ratings[0],
                'ratings' => $ratings,
                'applied_to' => count($ratings),
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

