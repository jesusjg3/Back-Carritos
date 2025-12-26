<?php

namespace App\Repositories;

use App\Models\TripPosition;

class TripPositionRepository
{
    public function getAll()
    {
        return TripPosition::all();
    }

    public function findById(int $id)
    {
        return TripPosition::findOrFail($id);
    }

    public function create(array $data)
    {
        return TripPosition::create($data);
    }

    public function update(int $id, array $data)
    {
        $tripPosition = TripPosition::findOrFail($id);
        $tripPosition->update($data);

        return $tripPosition;
    }

    public function delete(int $id)
    {
        $tripPosition = TripPosition::findOrFail($id);
        return $tripPosition->delete();
    }
}



