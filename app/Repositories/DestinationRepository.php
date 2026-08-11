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

    public function paginateAdminDestinations(int $perPage, ?string $search = null, ?string $status = null)
    {
        $query = Destination::withTrashed();

        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%")
                    ->orWhere('address', 'ilike', "%{$search}%");
            });
        }

        if ($status !== null) {
            if ($status === 'active') {
                $query->where('is_active', true)->whereNull('deleted_at');
            } elseif ($status === 'deleted') {
                $query->whereNotNull('deleted_at');
            } elseif ($status === 'inactive') {
                $query->where('is_active', false)->whereNull('deleted_at');
            }
        }

        $baseCountQuery = Destination::withTrashed();

        $counts = (clone $baseCountQuery)->selectRaw('
            COUNT(*) as total_registrados,
            COUNT(CASE WHEN is_active = false AND deleted_at IS NULL THEN 1 END) as total_inactivos,
            COUNT(CASE WHEN deleted_at IS NOT NULL THEN 1 END) as total_eliminados
        ')->first();

        $paginator = $query->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END ASC')
                           ->orderBy('id', 'desc')
                           ->paginate($perPage);

        $result = $paginator->toArray();
        $result['total_registrados'] = (int) ($counts->total_registrados ?? 0);
        $result['total_inactivos'] = (int) ($counts->total_inactivos ?? 0);
        $result['total_eliminados'] = (int) ($counts->total_eliminados ?? 0);

        return $result;
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
        $destination = Destination::findOrFail($id);
        return $destination->delete();
    }

    public function toggleStatus(int $id)
    {
        $destination = Destination::withTrashed()->findOrFail($id);
        $destination->is_active = !$destination->is_active;
        $destination->save();

        return $destination;
    }
}
