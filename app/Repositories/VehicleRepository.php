<?php

namespace App\Repositories;

use App\Models\Vehicle;

class VehicleRepository
{
    public function all($search = null, $status = null, $perPage = 10)
    {
        $query = Vehicle::query()->withTrashed();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('plate', 'ilike', '%' . $search . '%')
                  ->orWhere('model', 'ilike', '%' . $search . '%')
                  ->orWhere('brand', 'ilike', '%' . $search . '%');
            });
        }

        if ($status !== null && $status !== 'null' && $status !== '') {
            if ($status === 'deleted') {
                $query->whereNotNull('deleted_at');
            } elseif ($status === 'active') {
                $query->where('status', 'active')->whereNull('deleted_at');
            } else {
                $query->where('status', $status)->whereNull('deleted_at');
            }
        }

        $baseCountQuery = Vehicle::withTrashed();

        if ($search) {
            $baseCountQuery->where(function ($q) use ($search) {
                $q->where('plate', 'ilike', '%' . $search . '%')
                  ->orWhere('model', 'ilike', '%' . $search . '%')
                  ->orWhere('brand', 'ilike', '%' . $search . '%');
            });
        }

        $totalInactive = (clone $baseCountQuery)->where('status', 'inactive')->whereNull('deleted_at')->count();
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

    public function find(int $id)
    {
        return Vehicle::withTrashed()->findOrFail($id);
    }

    public function create(array $data)
    {
        return Vehicle::create([
            'brand' => $data['brand'],
            'model' => $data['model'],
            'plate' => $data['plate'],
            'color' => $data['color'],
            'capacity' => $data['capacity'],
            'status' => 'active',
        ]);
    }

    public function update(int $id, array $data)
    {
        $vehicle = $this->find($id);
        $vehicle->update($data);
        return $vehicle;
    }

    public function delete(int $id)
    {
        $vehicle = $this->find($id);
        return $vehicle->delete();
    }
}
