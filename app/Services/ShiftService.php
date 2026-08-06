<?php

namespace App\Services;

use App\Repositories\ShiftRepository;

class ShiftService
{
    protected ShiftRepository $shiftRepo;

    public function __construct(ShiftRepository $shiftRepo)
    {
        $this->shiftRepo = $shiftRepo;
    }

    public function getAllShifts($search = null, $perPage = 10)
    {
        return $this->shiftRepo->all($search, $perPage);
    }

    public function getShiftById(int $id)
    {
        return $this->shiftRepo->find($id);
    }

    public function createShift(array $data)
    {
        return $this->shiftRepo->create($data);
    }

    public function updateShift(int $id, array $data)
    {
        return $this->shiftRepo->update($id, $data);
    }

    public function deleteShift(int $id)
    {
        return $this->shiftRepo->delete($id);
    }

    public function toggleStatus(int $id)
    {
        return $this->shiftRepo->toggleStatus($id);
    }
}
