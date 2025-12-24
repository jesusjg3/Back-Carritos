<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStateRequest;
use App\Http\Requests\UpdateStateRequest;
use App\Services\StatesService;
use Illuminate\Http\JsonResponse;

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
        return response()->json($state, 201);
    }

    public function update(UpdateStateRequest $request, int $id): JsonResponse
    {
        $state = $this->statesService->updateState($id, $request->validated());
        return response()->json($state);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->statesService->deleteState($id);
        return response()->json(null, 204);
    }
}
