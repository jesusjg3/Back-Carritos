<?php

namespace App\Services;

use App\Repositories\VehicleRepository;

class VehicleService
{
    protected VehicleRepository $vehicleRepo;

    public function __construct(VehicleRepository $vehicleRepo)
    {
        $this->vehicleRepo = $vehicleRepo;
    }

    public function getAllVehicles($search = null, $status = null, $perPage = 10)
    {
        return $this->vehicleRepo->all($search, $status, $perPage);
    }

    public function getVehicleById(int $id)
    {
        return $this->vehicleRepo->find($id);
    }

    public function createVehicle(array $data)
    {
        return $this->vehicleRepo->create($data);
    }

    public function updateVehicle(int $id, array $data)
    {
        return $this->vehicleRepo->update($id, $data);
    }

    public function deleteVehicle(int $id)
    {
        return $this->vehicleRepo->delete($id);
    }
}
