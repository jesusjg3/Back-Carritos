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
     * Get All Trips for Administrator Log.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        if ($user && $user->rol_id === 1) {
            $perPage = $request->integer('per_page', 10);
            $search = $request->query('search');
            $stateName = $request->query('state_name');
            $trips = $this->tripService->getAllAdminTrips($perPage, $search, $stateName);
            return response()->json($trips);
        }
        return response()->json(['error' => 'No autorizado'], 403);
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
    public function accept(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $passengerId = $request->input('passenger_id'); // Optional for shared trips

        try {
            $updatedTrip = $this->tripService->acceptTripById($id, $user, $passengerId);
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
        $user = Auth::user();

        try {
            $updatedTrip = $this->tripService->startTrip($id, $user);
            return response()->json($updatedTrip);
        } catch (\Exception $e) {
            $status = $e->getCode() === 400 ? 400 : 500;
            return response()->json(['error' => $e->getMessage()], $status);
        }
    }

    public function boardPassenger(int $tripId, int $passengerId): JsonResponse
    {
        $user = Auth::user();

        try {
            $updatedTrip = $this->tripService->boardPassenger($tripId, $passengerId, $user);
            return response()->json($updatedTrip);
        } catch (\Exception $e) {
            $status = $e->getCode() === 403 ? 403 : 500;
            return response()->json(['error' => $e->getMessage()], $status);
        }
    }

    public function dropOffPassenger(int $tripId, int $passengerId): JsonResponse
    {
        $user = Auth::user();

        try {
            $updatedTrip = $this->tripService->dropOffPassenger($tripId, $passengerId, $user);
            return response()->json($updatedTrip);
        } catch (\Exception $e) {
            $status = $e->getCode() === 403 ? 403 : 500;
            return response()->json(['error' => $e->getMessage()], $status);
        }
    }

    /**
     * Finish a Trip (Driver/System).
     */
    public function finish(int $id): JsonResponse
    {
        $user = Auth::user();

        try {
            $updatedTrip = $this->tripService->finishTrip($id, $user);
            return response()->json($updatedTrip);
        } catch (\Exception $e) {
            $status = $e->getCode() === 403 ? 403 : 500;
            return response()->json(['error' => $e->getMessage()], $status);
        }
    }

    /**
     * Cancel a Trip.
     */
    public function cancel(int $id): JsonResponse
    {
        $user = Auth::user();
        try {
            $result = $this->tripService->cancelTrip($id, $user);
            return response()->json($result);
        } catch (\Exception $e) {
            $status = $e->getCode() === 403 ? 403 : 500;
            return response()->json(['error' => $e->getMessage()], $status);
        }
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
