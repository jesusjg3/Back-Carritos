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
        if ($search) {
            $baseCountQuery->where('name', 'ilike', '%' . $search . '%');
        }

        $totalInactive = (clone $baseCountQuery)->where('is_active', false)->whereNull('deleted_at')->count();
        $totalDeleted = (clone $baseCountQuery)->whereNotNull('deleted_at')->count();

        $paginator = $query->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END ASC')
                           ->orderBy('id', 'desc')
                           ->paginate($perPage);
        $result = $paginator->toArray();
        $result['total_registrados'] = (clone $baseCountQuery)->count();
        $result['total_inactivos'] = $totalInactive;
        $result['total_eliminados'] = $totalDeleted;

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
        $shift = $this->find($id);
        $shift->update($data);
        return $shift;
    }

    public function delete($id)
    {
        $shift = $this->find($id);
        $shift->delete();
        return true;
    }

    public function toggleStatus($id)
    {
        $shift = $this->find($id);
        $shift->is_active = !$shift->is_active;
        $shift->save();
        return $shift;
    }
}
