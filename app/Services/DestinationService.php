<?php

namespace App\Services;

use App\Repositories\DestinationRepository;

class DestinationService
{
    protected DestinationRepository $destinationRepo;

    public function __construct(DestinationRepository $destinationRepo)
    {
        $this->destinationRepo = $destinationRepo;
    }

    public function getAllDestinations()
    {
        return $this->destinationRepo->all();
    }

    public function getAllAdminDestinations()
    {
        return $this->destinationRepo->allIncludeInactive();
    }

    public function listDestinations(array $filters, $user = null)
    {
        if (isset($filters['only_active']) && filter_var($filters['only_active'], FILTER_VALIDATE_BOOLEAN)) {
            return $this->destinationRepo->all();
        }

        if (isset($filters['per_page'])) {
            $perPage = (int) $filters['per_page'];
            $search = $filters['search'] ?? null;
            $status = $filters['status'] ?? null;

            return $this->destinationRepo->paginateAdminDestinations($perPage, $search, $status);
        }

        if ($user && $user->rol_id === 1) {
            return $this->destinationRepo->allIncludeInactive();
        }

        return $this->destinationRepo->all();
    }

    public function getDestinationById(int $id)
    {
        return $this->destinationRepo->find($id);
    }

    public function createDestination(array $data)
    {
        return $this->destinationRepo->create($data);
    }

    public function updateDestination(int $id, array $data)
    {
        return $this->destinationRepo->update($id, $data);
    }

    public function deleteDestination(int $id)
    {
        return $this->destinationRepo->delete($id);
    }

    public function toggleDestinationStatus(int $id)
    {
        return $this->destinationRepo->toggleStatus($id);
    }
}
