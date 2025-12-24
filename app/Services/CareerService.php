<?php

namespace App\Services;

use App\Repositories\CareerRepository;
use App\Models\Career;
use App\Models\User;
use App\Models\State;
use Illuminate\Support\Facades\DB;

class CareerService
{
    protected CareerRepository $careerRepo;
    protected StatesService $statesService;

    public function __construct(CareerRepository $careerRepo, StatesService $statesService)
    {
        $this->careerRepo = $careerRepo;
        $this->statesService = $statesService;
    }

    public function getAllStates()
    {
        return $this->statesService->getAllStates();
    }

    /**
     * Usuario solicita carrera
     */
    public function requestCareer(array $data, User $user): Career
    {
        return DB::transaction(function () use ($data, $user) {
            // Nota: Asegúrate de que driver_id sea nullable en la migración de careers
            // si no se asigna al crear.
            return $this->careerRepo->create(array_merge($data, [
                'passenger_id' => $user->id,
                'state_id' => State::REQUESTED,
            ]));
        });
    }

    /**
     * Conductor acepta carrera
     */
    public function acceptCareer(Career $career, User $driver): Career
    {
        if ($career->state_id !== State::REQUESTED) {
            throw new \Exception('La carrera no está disponible');
        }

        return $this->careerRepo->update($career, [
            'driver_id' => $driver->id,
            'state_id' => State::ACCEPTED,
        ]);
    }

    /**
     * Finalizar carrera
     */
    public function finishCareer(Career $career): Career
    {
        return $this->careerRepo->update($career, [
            'state_id' => State::FINISHED,
        ]);
    }
}
