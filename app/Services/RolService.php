<?php

namespace App\Services;

use App\Repositories\RolRepository;

class RolService
{
    protected RolRepository $rolRepository;

    public function __construct(RolRepository $rolRepository)
    {
        $this->rolRepository = $rolRepository;
    }

    public function getAllRoles()
    {
        return $this->rolRepository->getAll();
    }

    public function getRolById(int $id)
    {
        return $this->rolRepository->findById($id);
    }

    public function createRol(array $data)
    {
        return $this->rolRepository->create($data);
    }

    public function updateRol(int $id, array $data)
    {
        return $this->rolRepository->update($id, $data);
    }

    public function deleteRol(int $id)
    {
        return $this->rolRepository->delete($id);
    }
}



