<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTripPositionRequest;
use App\Models\Trip;
use App\Services\TripPositionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TripPositionController extends Controller
{
    protected TripPositionService $positionService;

    public function __construct(TripPositionService $positionService)
    {
        $this->positionService = $positionService;
    }

    public function store(StoreTripPositionRequest $request, int $tripId): JsonResponse
    {
        try {
            $user = auth('api')->user();
            
            $position = $this->positionService->storePosition(
                $tripId,
                $user,
                $request->input('lat'),
                $request->input('lng'),
                $request->input('type')
            );

            return response()->json($position, 201);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Carrera no encontrada'], 404);
        } catch (\Exception $e) {
            $status = in_array($e->getCode(), [400, 403, 409], true) ? $e->getCode() : 400;
            return response()->json(['error' => $e->getMessage()], $status);
        }
    }


}


