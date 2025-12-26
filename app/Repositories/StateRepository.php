<?php

namespace App\Repositories;

use App\Models\State;

class StateRepository
{
    public function getAll()
    {
        return State::all();
    }

    public function findById(int $id)
    {
        return State::findOrFail($id);
    }

    public function create(array $data)
    {
        return State::create($data);
    }

    public function update(int $id, array $data)
    {
        $state = State::findOrFail($id);
        $state->update($data);

        return $state;
    }

    public function delete(int $id)
    {
        $state = State::findOrFail($id);
        return $state->delete();
    }
}



