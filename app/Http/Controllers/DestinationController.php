<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDestinationRequest;
use App\Http\Requests\UpdateDestinationRequest;
use App\Services\DestinationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DestinationController extends Controller
{
    protected DestinationService $destinationService;

    public function __construct(DestinationService $destinationService)
    {
        $this->destinationService = $destinationService;
    }

    public function index(Request $request): JsonResponse
    {
        $destinations = $this->destinationService->listDestinations($request->all(), auth('api')->user());
        return response()->json($destinations);
    }

    public function show(int $id): JsonResponse
    {
        $destination = $this->destinationService->getDestinationById($id);
        return response()->json($destination);
    }

    public function store(StoreDestinationRequest $request): JsonResponse
    {
        $destination = $this->destinationService->createDestination($request->validated());
        return response()->json($destination, 201);
    }

    public function update(UpdateDestinationRequest $request, int $id): JsonResponse
    {
        $destination = $this->destinationService->updateDestination($id, $request->validated());
        return response()->json($destination);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->destinationService->deleteDestination($id);
        return response()->json(null, 204);
    }

    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $destination = $this->destinationService->toggleDestinationStatus($id);
            return response()->json([
                'message' => 'Estado del destino actualizado',
                'destination' => $destination
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
