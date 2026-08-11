<?php

namespace App\Services;

use App\Repositories\EventRepository;
use Illuminate\Support\Facades\DB;

class EventService
{
    protected EventRepository $eventRepo;

    public function __construct(EventRepository $eventRepo)
    {
        $this->eventRepo = $eventRepo;
    }

    public function getAllEvents($search = null, $itemsPerPage = 15, $status = null)
    {
        return $this->eventRepo->all($search, $itemsPerPage, $status);
    }

    public function getEventById(int $id)
    {
        return $this->eventRepo->find($id);
    }

    public function createEvent(array $data)
    {
        return DB::transaction(function () use ($data) {
            $eventData = collect($data)->only(['name', 'description', 'start_date', 'end_date', 'is_active'])->toArray();
            $event = $this->eventRepo->create($eventData);

            if (isset($data['assignment_ids'])) {
                $this->eventRepo->syncAssignments($event, $data['assignment_ids']);
            }

            return $this->eventRepo->find($event->id);
        });
    }

    public function updateEvent(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $eventData = collect($data)->only(['name', 'description', 'start_date', 'end_date', 'is_active'])->toArray();
            $event = $this->eventRepo->update($id, $eventData);

            if (isset($data['assignment_ids'])) {
                $this->eventRepo->syncAssignments($event, $data['assignment_ids']);
            }

            return $this->eventRepo->find($event->id);
        });
    }

    public function deleteEvent(int $id)
    {
        return $this->eventRepo->delete($id);
    }
}
