<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    protected EventService $eventService;

    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    public function index(): JsonResponse
    {
        $events = $this->eventService->getAllEvents();
        return response()->json($events, 200);
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        $event = $this->eventService->createEvent($request->validated());
        return response()->json($event, 201);
    }

    public function show(int $id): JsonResponse
    {
        $event = $this->eventService->getEventById($id);
        return response()->json($event, 200);
    }

    public function update(UpdateEventRequest $request, int $id): JsonResponse
    {
        $event = $this->eventService->updateEvent($id, $request->validated());
        return response()->json($event, 200);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->eventService->deleteEvent($id);
        return response()->json(null, 204);
    }
}
