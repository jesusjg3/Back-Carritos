<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCareerRequest;
use App\Models\Career;
use App\Services\CareerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CareerController extends Controller
{
    protected CareerService $careerService;

    public function __construct(CareerService $careerService)
    {
        $this->careerService = $careerService;
    }

    /**
     * Request a new career (Passenger).
     */
    public function request(StoreCareerRequest $request): JsonResponse
    {
        $user = Auth::user();
        $career = $this->careerService->requestCareer($request->validated(), $user);

        return response()->json($career, 201);
    }

    /**
     * Accept a pending career (Driver).
     */
    public function accept(int $id): JsonResponse
    {
        $user = Auth::user();
        // We might want to use repository here to find, but Service usually takes Career object.
        // Or we can let Route Model Binding do it if we change signature to (Career $career).
        // For now, I'll find it manually or assume route binding isn't set up yet for implicit binding
        // strictly by ID without explicit mapping. But `find` throws, so it's safe.
        // To be safe with "int $id", I'll use Model::findOrFail inside or via Repo?
        // Service expects `Career $career`.

        $career = Career::findOrFail($id);

        try {
            $updatedCareer = $this->careerService->acceptCareer($career, $user);
            return response()->json($updatedCareer);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Finish a career (Driver/System).
     */
    public function finish(int $id): JsonResponse
    {
        $career = Career::findOrFail($id);

        $updatedCareer = $this->careerService->finishCareer($career);
        return response()->json($updatedCareer);
    }
}
