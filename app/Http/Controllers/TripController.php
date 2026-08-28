<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTripRequest;
use App\Models\Trip;
use App\Services\TripService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        $perPage = $request->integer('per_page', 10);
        $search = $request->query('search');
        $stateName = $request->query('state_name');
        $trips = $this->tripService->getAllAdminTrips($perPage, $search, $stateName);
        return response()->json($trips);
    }

    /**
     * Request a new Trip (Passenger).
     */
    public function request(StoreTripRequest $request): JsonResponse
    {
        $user = Auth::user();

        try {
            $trip = $this->tripService->requestTrip($request->validated(), $user);
        } catch (\Exception $e) {
            $status = in_array($e->getCode(), [400, 403, 409], true) ? $e->getCode() : 500;
            return response()->json(['error' => $e->getMessage()], $status);
        }

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
            $status = in_array($e->getCode(), [400, 403, 409], true) ? $e->getCode() : 400;
            return response()->json(['error' => $e->getMessage()], $status);
        }
    }

    /**
     * Accept a joining passenger for an active Trip (Driver).
     */
    public function acceptPassenger(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $passengerId = $request->input('passenger_id');

        if (!$passengerId) {
            return response()->json(['error' => 'Se requiere el ID del pasajero.'], 400);
        }

        try {
            $updatedTrip = $this->tripService->acceptPassengerInTrip($id, $user, $passengerId);
            return response()->json($updatedTrip);
        } catch (\Exception $e) {
            $status = in_array($e->getCode(), [400, 403, 409], true) ? $e->getCode() : 400;
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
            $status = in_array($e->getCode(), [400, 403, 409], true) ? $e->getCode() : 500;
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
            $status = in_array($e->getCode(), [400, 403, 409], true) ? $e->getCode() : 500;
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
            $status = in_array($e->getCode(), [400, 403, 409], true) ? $e->getCode() : 500;
            return response()->json(['error' => $e->getMessage()], $status);
        }
    }

    public function cancelPassenger(int $tripId, int $passengerId): JsonResponse
    {
        $user = Auth::user();

        try {
            $updatedTrip = $this->tripService->cancelPassenger($tripId, $passengerId, $user);
            return response()->json($updatedTrip);
        } catch (\Exception $e) {
            Log::error("cancelPassenger Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            $status = in_array($e->getCode(), [400, 403, 409], true) ? $e->getCode() : 500;
            return response()->json(['error' => $e->getMessage()], $status);
        }
    }

    public function rejectPassenger(int $tripId, int $passengerId): JsonResponse
    {
        $user = Auth::user();

        try {
            $updatedTrip = $this->tripService->rejectPassenger($tripId, $passengerId, $user);
            return response()->json($updatedTrip);
        } catch (\Exception $e) {
            Log::error("rejectPassenger Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            $status = in_array($e->getCode(), [400, 403, 409], true) ? $e->getCode() : 500;
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
            $status = in_array($e->getCode(), [400, 403, 409], true) ? $e->getCode() : 500;
            return response()->json(['error' => $e->getMessage()], $status);
        }
    }

    /**
     * Get Current Active Trip for User
     */
    public function current(): JsonResponse
    {
        $user = Auth::user();
        
        try {
            $currentTrip = $this->tripService->getCurrentActiveTrip($user);
            if ($currentTrip) {
                return response()->json(['trip' => $currentTrip]);
            }
            return response()->json(['trip' => null], 200);
        } catch (\Exception $e) {
            Log::error("store Trip Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Cancel a Trip.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $reason = $request->validate([
            'reason' => 'nullable|string|max:255',
        ])['reason'] ?? null;
        
        try {
            $result = $this->tripService->cancelTrip($id, $user, $reason);
            return response()->json($result);
        } catch (\Exception $e) {
            $status = in_array($e->getCode(), [400, 403, 409]) ? $e->getCode() : 500;
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
