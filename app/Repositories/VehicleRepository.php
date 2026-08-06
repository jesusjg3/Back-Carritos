<?php

namespace App\Repositories;

use App\Models\Vehicle;

class VehicleRepository
{
    public function all($search = null, $status = null, $perPage = 10)
    {
        $query = Vehicle::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('plate', 'ilike', '%' . $search . '%')
                  ->orWhere('model', 'ilike', '%' . $search . '%')
                  ->orWhere('brand', 'ilike', '%' . $search . '%');
            });
        }

        if ($status !== null && $status !== 'null' && $status !== '') {
            $query->where('status', $status);
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function find(int $id)
    {
        return Vehicle::findOrFail($id);
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
