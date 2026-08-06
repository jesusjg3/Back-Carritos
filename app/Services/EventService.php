<?php

namespace App\Services;

use App\Repositories\EventRepository;

class EventService
{
    protected EventRepository $eventRepo;

    public function __construct(EventRepository $eventRepo)
    {
        $this->eventRepo = $eventRepo;
    }

    public function getAllEvents()
    {
        return $this->eventRepo->all();
    }

    public function getEventById(int $id)
    {
        return $this->eventRepo->find($id);
    }

    public function createEvent(array $data)
    {
        $eventData = collect($data)->only(['name', 'description', 'start_date', 'end_date'])->toArray();
        $event = $this->eventRepo->create($eventData);

        if (isset($data['vehicle_ids'])) {
            $this->eventRepo->syncVehicles($event, $data['vehicle_ids']);
        }

        return $this->eventRepo->find($event->id);
    }

    public function updateEvent(int $id, array $data)
    {
        $eventData = collect($data)->only(['name', 'description', 'start_date', 'end_date'])->toArray();
        $event = $this->eventRepo->update($id, $eventData);

        if (isset($data['vehicle_ids'])) {
            $this->eventRepo->syncVehicles($event, $data['vehicle_ids']);
        }

        return $this->eventRepo->find($event->id);
    }

    public function deleteEvent(int $id)
    {
        return $this->eventRepo->delete($id);
    }
}
