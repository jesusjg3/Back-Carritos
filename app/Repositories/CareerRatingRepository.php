<?php

namespace App\Repositories;

use App\Models\CareerRating;

class CareerRatingRepository
{
    public function getAll()
    {
        return CareerRating::all();
    }

    public function findById(int $id)
    {
        return CareerRating::findOrFail($id);
    }

    public function create(array $data)
    {
        return CareerRating::create($data);
    }

    public function update(int $id, array $data)
    {
        $careerRating = CareerRating::findOrFail($id);
        $careerRating->update($data);

        return $careerRating;
    }

    public function delete(int $id)
    {
        $careerRating = CareerRating::findOrFail($id);
        return $careerRating->delete();
    }
}
