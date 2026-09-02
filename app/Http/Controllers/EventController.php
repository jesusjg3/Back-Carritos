<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Services\EventService;
use App\Models\AuditLog;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    protected EventService $eventService;

    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    public function index(Request $request): JsonResponse
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $itemsPerPage = $request->query('per_page', 15);
        $events = $this->eventService->getAllEvents($search, $itemsPerPage, $status);
        return response()->json($events, 200);
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        $event = $this->eventService->createEvent($request->validated());
        AuditLog::record('event.created', $event, [], $event->toArray());
        return response()->json($event, 201);
    }

    public function show(int $id): JsonResponse
    {
        $event = $this->eventService->getEventById($id);
        return response()->json($event, 200);
    }

    public function update(UpdateEventRequest $request, int $id): JsonResponse
    {
        $before = Event::findOrFail($id)->toArray();
        $event = $this->eventService->updateEvent($id, $request->validated());
        AuditLog::record('event.updated', $event, $before, $event->toArray());
        return response()->json($event, 200);
    }

    public function destroy(int $id): JsonResponse
    {
        $before = Event::findOrFail($id)->toArray();
        $this->eventService->deleteEvent($id);
        AuditLog::record('event.deleted', $id, $before);
        return response()->json(null, 204);
    }
}
