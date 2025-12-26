<?php

namespace App\Repositories;

use App\Models\Tabs;

class TabsRepository
{
    public function getAll()
    {
        return Tabs::all();
    }

    public function findById(int $id)
    {
        return Tabs::findOrFail($id);
    }

    public function create(array $data)
    {
        return Tabs::create($data);
    }

    public function update(int $id, array $data)
    {
        $tab = Tabs::findOrFail($id);
        $tab->update($data);

        return $tab;
    }

    public function delete(int $id)
    {
        $tab = Tabs::findOrFail($id);
        return $tab->delete();
    }
}



