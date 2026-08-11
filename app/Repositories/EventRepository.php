<?php

namespace App\Repositories;

use App\Models\Event;
use Illuminate\Support\Facades\DB;

class EventRepository
{
    public function all()
    {
        return Event::with(['assignments.user', 'assignments.vehicle', 'assignments.shift'])->orderBy('start_date', 'asc')->get();
    }

    public function find($id)
    {
        return Event::with(['assignments.user', 'assignments.vehicle', 'assignments.shift'])->findOrFail($id);
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

    public function getActiveEventAssignmentIds(): array
    {
        return DB::table('assignment_event')
            ->join('events', 'events.id', '=', 'assignment_event.event_id')
            ->where('events.start_date', '<=', now())
            ->where('events.end_date', '>=', now())
            ->pluck('assignment_event.assignment_id')
            ->toArray();
    }

    public function getActiveEventVehicleIds(): array
    {
        return DB::table('assignment_event')
            ->join('events', 'events.id', '=', 'assignment_event.event_id')
            ->join('assignments', 'assignments.id', '=', 'assignment_event.assignment_id')
            ->where('events.start_date', '<=', now())
            ->where('events.end_date', '>=', now())
            ->whereNull('assignments.deleted_at')
            ->pluck('assignments.vehicle_id')
            ->filter()
            ->unique()
            ->toArray();
    }

    public function delete($id)
    {
        $event = $this->find($id);
        $event->delete();
        return true;
    }

    public function syncAssignments(Event $event, array $assignmentIds)
    {
        $event->assignments()->sync($assignmentIds);
    }
}
