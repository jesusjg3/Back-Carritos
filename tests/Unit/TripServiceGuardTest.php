<?php

namespace Tests\Unit;

use App\Models\Rol;
use App\Models\State;
use App\Models\Trip;
use App\Models\User;
use App\Repositories\AssignmentRepository;
use App\Repositories\TripRepository;
use App\Services\ExpoPushService;
use App\Services\StatesService;
use App\Services\TripService;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class TripServiceGuardTest extends TestCase
{
    public function test_cannot_start_a_requested_trip(): void
    {
        $trip = $this->trip(State::REQUESTED);
        $tripRepository = Mockery::mock(TripRepository::class);
        $tripRepository->shouldReceive('findLocked')->once()->andReturn($trip);

        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('Solo se puede iniciar un viaje aceptado.');

        $this->transaction();
        $this->service($tripRepository)->startTrip(1, $this->driver());
    }

    public function test_cannot_finish_an_accepted_trip(): void
    {
        $trip = $this->trip(State::ACCEPTED);
        $tripRepository = Mockery::mock(TripRepository::class);
        $tripRepository->shouldReceive('findLocked')->once()->andReturn($trip);

        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('Solo se puede finalizar un viaje iniciado.');

        $this->transaction();
        $this->service($tripRepository)->finishTrip(1, $this->driver());
    }

    public function test_cannot_board_a_passenger_not_in_the_trip(): void
    {
        $trip = $this->trip(State::STARTED);
        $tripRepository = Mockery::mock(TripRepository::class);
        $tripRepository->shouldReceive('findLocked')->once()->andReturn($trip);
        $tripRepository->shouldReceive('getPassengerInTrip')->once()->andReturn(null);

        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('El pasajero no tiene una solicitud aceptada para abordar.');

        $this->transaction();
        $this->service($tripRepository)->boardPassenger(1, 999, $this->driver());
    }

    public function test_passenger_cannot_cancel_a_trip_they_do_not_belong_to(): void
    {
        $trip = $this->trip(State::ACCEPTED);
        $tripRepository = Mockery::mock(TripRepository::class);
        $tripRepository->shouldReceive('findLocked')->once()->andReturn($trip);
        $tripRepository->shouldReceive('getPassengerInTrip')->once()->andReturn(null);

        $passenger = User::make(['name' => 'Pasajero']);
        $passenger->id = 20;
        $passenger->setRelation('rol', Rol::make(['rol_name' => 'pasajero']));

        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('No perteneces a un viaje activo con este identificador.');

        $this->transaction();
        $this->service($tripRepository)->cancelTrip(1, $passenger);
    }

    private function service(TripRepository $tripRepository): TripService
    {
        return new TripService(
            $tripRepository,
            Mockery::mock(AssignmentRepository::class),
            Mockery::mock(StatesService::class),
            Mockery::mock(ExpoPushService::class),
        );
    }

    private function transaction(): void
    {
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback());
    }

    private function trip(int $state): Trip
    {
        $trip = Trip::make([
            'state_id' => $state,
            'driver_id' => 10,
        ]);
        $trip->id = 1;

        return $trip;
    }

    private function driver(): User
    {
        $driver = User::make(['name' => 'Conductor']);
        $driver->id = 10;

        return $driver;
    }
}
