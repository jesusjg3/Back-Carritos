<?php

namespace App\Repositories;

use App\Models\Destination;

class DestinationRepository
{
    public function all()
    {
        return Destination::where('is_active', true)->get();
    }

    public function allIncludeInactive()
    {
        return Destination::withTrashed()->get();
    }

    public function find(int $id)
    {
        return Destination::findOrFail($id);
    }

    public function create(array $data)
    {
        return Destination::create($data);
    }

    public function update(int $id, array $data)
    {
        $destination = Destination::findOrFail($id);
        $destination->update($data);

        return $destination;
    }

    public function delete(int $id)
    {
        $destination = Destination::findOrFail($id);
        return $destination->delete();
    }

    public function restore(int $id)
    {
        $destination = Destination::withTrashed()->findOrFail($id);
        $destination->restore();
        return $destination;
    }

    public function toggleStatus(int $id)
    {
        $destination = Destination::findOrFail($id);
        $destination->is_active = !$destination->is_active;
        $destination->save();

        return $destination;
    }
}
