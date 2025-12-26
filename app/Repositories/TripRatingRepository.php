<?php

namespace App\Repositories;

use App\Models\TripRating;

class TripRatingRepository
{
    public function getAll()
    {
        return TripRating::all();
    }

    public function findById(int $id)
    {
        return TripRating::findOrFail($id);
    }

    public function create(array $data)
    {
        return TripRating::create($data);
    }

    public function update(int $id, array $data)
    {
        $tripRating = TripRating::findOrFail($id);
        $tripRating->update($data);

        return $tripRating;
    }

    public function delete(int $id)
    {
        $tripRating = TripRating::findOrFail($id);
        return $tripRating->delete();
    }
}



