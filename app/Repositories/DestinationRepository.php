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

    public function paginateAdminDestinations(int $perPage, ?string $search = null, ?bool $isActive = null)
    {
        $query = Destination::withTrashed();

        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%")
                    ->orWhere('address', 'ilike', "%{$search}%");
            });
        }

        if ($isActive !== null) {
            $query->where('is_active', $isActive);
            if (!$isActive) {
                $query->whereNotNull('deleted_at');
            } else {
                $query->whereNull('deleted_at');
            }
        }

        return $query->paginate($perPage);
    }

    public function find(int $id)
    {
        return Destination::withTrashed()->findOrFail($id);
    }

    public function create(array $data)
    {
        return Destination::create($data);
    }

    public function update(int $id, array $data)
    {
        $destination = Destination::withTrashed()->findOrFail($id);
        $destination->update($data);

        return $destination;
    }

    public function delete(int $id)
    {
        $destination = Destination::withTrashed()->findOrFail($id);
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
        $destination = Destination::withTrashed()->findOrFail($id);
        $destination->is_active = !$destination->is_active;
        $destination->save();

        return $destination;
    }
}
