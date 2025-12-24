<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCareerPositionRequest;
use App\Models\Career;
use App\Services\CareerPositionService;
use Illuminate\Http\JsonResponse;

class CareerPositionController extends Controller
{
    protected CareerPositionService $positionService;

    public function __construct(CareerPositionService $positionService)
    {
        $this->positionService = $positionService;
    }

    public function store(StoreCareerPositionRequest $request, int $careerId): JsonResponse
    {
        $career = Career::findOrFail($careerId);

        $position = $this->positionService->storePosition(
            $career,
            $request->input('lat'),
            $request->input('lng'),
            $request->input('type')
        );

        return response()->json($position, 201);
    }
}
