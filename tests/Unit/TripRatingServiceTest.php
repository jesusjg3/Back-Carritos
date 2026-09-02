<?php

namespace Tests\Unit;

use App\Models\State;
use App\Models\Trip;
use App\Models\TripPassenger;
use App\Models\TripRating;
use App\Models\User;
use App\Repositories\TripRatingRepository;
use App\Repositories\TripRepository;
use App\Repositories\UserRepository;
use App\Services\TripRatingService;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class TripRatingServiceTest extends TestCase
{
    public function test_one_driver_evaluation_is_applied_to_all_dropped_off_passengers(): void
    {
        $driver = User::make(['name' => 'Conductor']);
        $driver->id = 10;

        $passengerOne = $this->passenger(20, 'Pasajero Uno');
        $passengerTwo = $this->passenger(21, 'Pasajero Dos');

        $trip = Trip::make([
            'state_id' => State::FINISHED,
            'driver_id' => $driver->id,
        ]);
        $trip->id = 99;
        $trip->setRelation('passengers', collect([$passengerOne, $passengerTwo]));

        $tripRepository = Mockery::mock(TripRepository::class);
        $tripRepository->shouldReceive('find')->once()->with(99)->andReturn($trip);

        $ratingRepository = Mockery::mock(TripRatingRepository::class);
        $createdRatingId = 0;
        $ratingRepository->shouldReceive('create')
            ->twice()
            ->andReturnUsing(function (array $attributes) use (&$createdRatingId) {
                $rating = new TripRating($attributes);
                $rating->id = ++$createdRatingId;
                $rating->created_at = now();
                return $rating;
            });

        $userRepository = Mockery::mock(UserRepository::class);
        $userRepository->shouldReceive('getRatingProfileForUpdate')
            ->twice()
            ->andReturn((object) ['score' => 5, 'rating_count' => 0]);
        $userRepository->shouldReceive('updateRatingProfile')->twice();

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback());

        $service = new TripRatingService($ratingRepository, $tripRepository, $userRepository);
        $result = $service->rate(99, $driver, 4, 'Buen comportamiento');

        $this->assertSame(2, $result['applied_to']);
        $this->assertCount(2, $result['ratings']);
        $this->assertSame('Pasajero Uno', $result['ratings'][0]['receiver']['name']);
        $this->assertSame('Pasajero Dos', $result['ratings'][1]['receiver']['name']);
        $this->assertSame('Buen comportamiento', $result['ratings'][0]['comment']);
        $this->assertSame('Buen comportamiento', $result['ratings'][1]['comment']);
    }

    private function passenger(int $id, string $name): User
    {
        $passenger = User::make(['name' => $name]);
        $passenger->id = $id;

        $pivot = new Pivot();
        $pivot->setAttribute('status', TripPassenger::STATUS_DROPPED_OFF);
        $passenger->setRelation('pivot', $pivot);

        return $passenger;
    }
}
