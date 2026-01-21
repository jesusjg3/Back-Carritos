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
     * Start a Trip (Driver picked up passenger).
     */
    public function start(int $id): JsonResponse
    {
        $trip = Trip::findOrFail($id);

        try {
            $updatedTrip = $this->tripService->startTrip($trip);
            return response()->json($updatedTrip);
        } catch (\Exception $e) {
            $status = $e->getCode() === 400 ? 400 : 500;
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
    /**
     * Get Trip History for Passenger.
     */
    public function history(): JsonResponse
    {
        $user = Auth::user();
        $history = $this->tripService->getTripHistory($user);
        return response()->json($history);
    }
}
