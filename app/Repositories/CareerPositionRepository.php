<?php

namespace App\Repositories;

use App\Models\CareerPosition;

class CareerPositionRepository
{
    public function getAll()
    {
        return CareerPosition::all();
    }

    public function findById(int $id)
    {
        return CareerPosition::findOrFail($id);
    }

    public function create(array $data)
    {
        return CareerPosition::create($data);
    }

    public function update(int $id, array $data)
    {
        $careerPosition = CareerPosition::findOrFail($id);
        $careerPosition->update($data);

        return $careerPosition;
    }

    public function delete(int $id)
    {
        $careerPosition = CareerPosition::findOrFail($id);
        return $careerPosition->delete();
    }
}
