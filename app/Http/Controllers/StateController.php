<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStateRequest;
use App\Http\Requests\UpdateStateRequest;
use App\Services\StatesService;
use Illuminate\Http\JsonResponse;
use App\Models\AuditLog;
use App\Models\State;

class StateController extends Controller
{
    protected StatesService $statesService;

    public function __construct(StatesService $statesService)
    {
        $this->statesService = $statesService;
    }

    public function index(): JsonResponse
    {
        $states = $this->statesService->getAllStates();
        return response()->json($states);
    }

    public function show(int $id): JsonResponse
    {
        $state = $this->statesService->getStateById($id);
        return response()->json($state);
    }

    public function store(StoreStateRequest $request): JsonResponse
    {
        $state = $this->statesService->createState($request->validated());
        AuditLog::record('state.created', $state, [], $state->toArray());
        return response()->json($state, 201);
    }

    public function update(UpdateStateRequest $request, int $id): JsonResponse
    {
        $before = State::findOrFail($id)->toArray();
        $state = $this->statesService->updateState($id, $request->validated());
        AuditLog::record('state.updated', $state, $before, $state->toArray());
        return response()->json($state);
    }

    public function destroy(int $id): JsonResponse
    {
        $before = State::findOrFail($id)->toArray();
        $this->statesService->deleteState($id);
        AuditLog::record('state.deleted', $id, $before);
        return response()->json(null, 204);
    }
}


