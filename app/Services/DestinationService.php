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
