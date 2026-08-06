<?php

namespace App\Repositories;

use App\Models\Shift;

class ShiftRepository
{
    public function all($search = null, $perPage = 10)
    {
        $query = Shift::query();

        if ($search) {
            $query->where('name', 'ilike', '%' . $search . '%');
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function find($id)
    {
        return Shift::findOrFail($id);
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
