<?php

namespace App\Repositories;

use App\Models\Event;

class EventRepository
{
    public function all()
    {
        return Event::with('vehicles')->orderBy('start_date', 'asc')->get();
    }

    public function find($id)
    {
        return Event::with('vehicles')->findOrFail($id);
    }

    public function create(array $data)
    {
        return Event::create($data);
    }

    public function update($id, array $data)
    {
        $event = $this->find($id);
        $event->update($data);
        return $event;
    }

    public function delete($id)
    {
        $event = $this->find($id);
        $event->delete();
        return true;
    }

    public function syncVehicles(Event $event, array $vehicleIds)
    {
        $event->vehicles()->sync($vehicleIds);
    }
}
