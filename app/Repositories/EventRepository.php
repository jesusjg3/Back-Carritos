<?php

namespace App\Repositories;

use App\Models\Event;
use Illuminate\Support\Facades\DB;

class EventRepository
{
    public function all($search = null, $itemsPerPage = 15, $status = null)
    {
        $query = Event::withTrashed()->with(['assignments.user', 'assignments.vehicle'])->withCount('assignments');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        if ($status) {
            $now = now();
            if ($status === 'active') {
                $query->where('start_date', '<=', $now)->where('end_date', '>=', $now)->whereNull('deleted_at');
            } elseif ($status === 'future') {
                $query->where('start_date', '>', $now)->whereNull('deleted_at');
            } elseif ($status === 'past') {
                $query->where('end_date', '<', $now)->whereNull('deleted_at');
            } elseif ($status === 'deleted') {
                $query->whereNotNull('deleted_at');
            }
        }

        $baseCountQuery = Event::withTrashed();
        $totalEventos = $baseCountQuery->count();
        $now = now();
        $eventosActivos = (clone $baseCountQuery)->where('start_date', '<=', $now)->where('end_date', '>=', $now)->count();
        
        $conductoresReservados = DB::table('assignment_event')
            ->join('events', 'events.id', '=', 'assignment_event.event_id')
            ->where('events.start_date', '<=', $now)
            ->where('events.end_date', '>=', $now)
            ->distinct('assignment_event.assignment_id')
            ->count('assignment_event.assignment_id');

        $paginator = $query->orderBy('start_date', 'asc')->paginate($itemsPerPage);
        
        $result = $paginator->toArray();
        $result['total_registrados'] = $totalEventos;
        $result['total_activos'] = $eventosActivos;
        $result['total_conductores_reservados'] = $conductoresReservados;

        return $result;
    }

    public function find($id)
    {
        return Event::with([
            'assignments.user:id,name,email,rol_id',
            'assignments.vehicle:id,plate,brand',
            'assignments.shift:id,name,start_time,end_time'
        ])->findOrFail($id);
    }

    public function create(array $data)
    {
        return Event::create($data);
    }

    public function update($id, array $data)
    {
        $event = Event::findOrFail($id);
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
        $event = Event::findOrFail($id);
        $event->delete();
        return true;
    }

    public function syncAssignments(Event $event, array $assignmentIds)
    {
        $event->assignments()->sync($assignmentIds);
    }
}
