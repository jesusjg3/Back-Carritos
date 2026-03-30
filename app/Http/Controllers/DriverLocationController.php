<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDriverLocationRequest;
use App\Http\Requests\GetNearbyDriversRequest;
use App\Services\DriverLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverLocationController extends Controller
{
    protected DriverLocationService $locationService;

    public function __construct(DriverLocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    public function updateDriverLocation(UpdateDriverLocationRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();

            $location = $this->locationService->updateLocation(
                $user,
                $request->latitude,
                $request->longitude
            );

            return response()->json([
                'message' => 'Ubicación actualizada correctamente',
                'location' => [
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'updated_at' => $location->last_update,
                ]
            ]);
        } catch (\Exception $e) {
            $code = $e->getCode();
            return response()->json(['error' => $e->getMessage()], is_int($code) && $code >= 100 && $code < 600 ? $code : 500);
        }
    }

    public function getNearbyDrivers(GetNearbyDriversRequest $request): JsonResponse
    {
        try {
            $drivers = $this->locationService->getNearbyDrivers(
                $request->latitude,
                $request->longitude,
                $request->input('radius', 5)
            );

            return response()->json([
                'drivers' => $drivers,
                'count' => $drivers->count(),
                'search_center' => [
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                ],
                'radius_km' => $request->input('radius', 5),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function setDriverOffline(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $this->locationService->setOffline($user);
            return response()->json(['message' => 'Status set to offline']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
