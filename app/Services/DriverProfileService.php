<?php

namespace App\Services;

use App\Repositories\DriverProfileRepository;

class DriverProfileService
{
    protected DriverProfileRepository $driverProfileRepo;

    public function __construct(DriverProfileRepository $driverProfileRepo)
    {
        $this->driverProfileRepo = $driverProfileRepo;
    }

    public function getAllProfiles($search = null, $perPage = 10, $status = null)
    {
        return $this->driverProfileRepo->all($search, $perPage, $status);
    }

    public function getProfileById(int $id)
    {
        return $this->driverProfileRepo->find($id);
    }

    public function createProfile(array $data)
    {
        // Any business logic would go here
        return $this->driverProfileRepo->create($data);
    }

    public function updateProfile(int $id, array $data)
    {
        // Any business logic would go here
        return $this->driverProfileRepo->update($id, $data);
    }

    public function deleteProfile(int $id)
    {
        return $this->driverProfileRepo->delete($id);
    }

    public function toggleStatus(int $id)
    {
        return $this->driverProfileRepo->toggleStatus($id);
    }
}
