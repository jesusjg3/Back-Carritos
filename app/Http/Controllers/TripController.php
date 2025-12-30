<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTripRequest;
use App\Models\Trip;
use App\Services\TripService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TripController extends Controller
{
    protected TripService $tripService;

    public function __construct(TripService $tripService)
    {
        $this->tripService = $tripService;
    }

    /**
     * Request a new Trip (Passenger).
     */
    public function request(StoreTripRequest $request): JsonResponse
    {
        $user = Auth::user();
        $trip = $this->tripService->requestTrip($request->validated(), $user);

        return response()->json($trip, 201);
    }

    /**
     * Accept a pending Trip (Driver).
     */
    public function accept(int $id): JsonResponse
    {
        $user = Auth::user();

        try {
            $updatedTrip = $this->tripService->acceptTripById($id, $user);
            return response()->json($updatedTrip);
        } catch (\Exception $e) {
            $status = $e->getCode() === 409 ? 409 : 400;
            return response()->json(['error' => $e->getMessage()], $status);
        }
    }

    /**
     * Finish a Trip (Driver/System).
     */
    public function finish(int $id): JsonResponse
    {
        $trip = Trip::findOrFail($id);

        $updatedTrip = $this->tripService->finishTrip($trip);
        return response()->json($updatedTrip);
    }
}
