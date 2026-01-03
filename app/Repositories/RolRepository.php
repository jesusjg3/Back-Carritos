<?php

namespace App\Repositories;

use App\Models\Rol;

class RolRepository
{
    public function all()
    {
        return Rol::all();
    }

    public function getAll()
    {
        return Rol::all();
    }

    public function find(int $id)
    {
        return Rol::find($id);
    }

    public function findById(int $id)
    {
        return Rol::findOrFail($id);
    }

    public function findByName(string $name)
    {
        return Rol::where('rol_name', $name)->first();
    }

    public function create(array $data)
    {
        return Rol::create($data);
    }

    public function update(int $id, array $data)
    {
        $rol = Rol::findOrFail($id);
        $rol->update($data);

        return $rol;
    }

    public function delete(int $id)
    {
        $rol = Rol::findOrFail($id);
        return $rol->delete();
    }
}



