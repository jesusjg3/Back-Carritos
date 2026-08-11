<?php

namespace App\Repositories;

use App\Models\Shift;

class ShiftRepository
{
    public function all($search = null, $perPage = 10, $status = null)
    {
        $query = Shift::withTrashed();

        if ($search) {
            $query->where('name', 'ilike', '%' . $search . '%');
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

        $baseCountQuery = Shift::withTrashed();



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

    public function find($id)
    {
        return Shift::withTrashed()->findOrFail($id);
    }

    public function create(array $data)
    {
        return Shift::create($data);
    }

    public function update($id, array $data)
    {
        $shift = Shift::withTrashed()->findOrFail($id);
        $shift->update($data);
        return $shift;
    }

    public function delete($id)
    {
        $shift = Shift::withTrashed()->findOrFail($id);
        $shift->delete();
        return true;
    }

    public function toggleStatus($id)
    {
        $shift = Shift::withTrashed()->findOrFail($id);
        $shift->is_active = !$shift->is_active;
        $shift->save();
        return $shift;
    }
}
