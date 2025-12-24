<?php

namespace App\Repositories;

use App\Models\Career;

class CareerRepository
{
    public function all()
    {
        return Career::all();
    }

    public function find(int $id): Career
    {
        return Career::findOrFail($id);
    }

    public function create(array $data): Career
    {
        return Career::create($data);
    }

    public function getByDriver(int $driverId)
    {
        return Career::where('driver_id', $driverId)->get();
    }

    public function update(Career $career, array $data): Career
    {
        $career->update($data);
        return $career;
    }

    public function delete(Career $career): bool
    {
        return $career->delete();
    }
}
