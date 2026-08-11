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

        if ($status) {
            if ($status === 'deleted') {
                $query->whereNotNull('deleted_at');
            } elseif ($status === 'active') {
                $query->where('status', 'active')->whereNull('deleted_at');
            } else {
                $query->where('status', $status)->whereNull('deleted_at');
            }
        }

        $baseCountQuery = Vehicle::withTrashed();



        $counts = (clone $baseCountQuery)->selectRaw('
            COUNT(*) as total_registrados,
            COUNT(CASE WHEN status = \'inactive\' AND deleted_at IS NULL THEN 1 END) as total_inactivos,
            COUNT(CASE WHEN status = \'maintenance\' AND deleted_at IS NULL THEN 1 END) as total_mantenimiento,
            COUNT(CASE WHEN deleted_at IS NOT NULL THEN 1 END) as total_eliminados
        ')->first();

        $paginator = $query->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END ASC')
                           ->orderBy('id', 'desc')
                           ->paginate($perPage);
        $result = $paginator->toArray();
        $result['total_registrados'] = (int) ($counts->total_registrados ?? 0);
        $result['total_inactivos'] = (int) ($counts->total_inactivos ?? 0);
        $result['total_mantenimiento'] = (int) ($counts->total_mantenimiento ?? 0);
        $result['total_eliminados'] = (int) ($counts->total_eliminados ?? 0);

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
        $vehicle = Vehicle::withTrashed()->findOrFail($id);
        $vehicle->update($data);
        return $vehicle;
    }

    public function delete(int $id)
    {
        $vehicle = Vehicle::withTrashed()->findOrFail($id);
        return $vehicle->delete();
    }
}
