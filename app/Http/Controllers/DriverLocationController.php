<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDriverLocationRequest;
use App\Http\Requests\GetNearbyDriversRequest;
use App\Services\LocationService;
use App\Services\DisconnectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverLocationController extends Controller
{
    public function __construct(
        protected LocationService $locationService,
        protected DisconnectService $disconnectService
    ) {}

    public function updateDriverLocation(UpdateDriverLocationRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->rol || $user->rol->rol_name !== 'conductor') {
            return response()->json(['error' => 'Solo los conductores pueden actualizar su ubicación'], 403);
        }

        $data = $request->validated();

        $payload = $this->locationService->updateLocationAndBroadcast(
            $user->id,
            $user->name,
            $data
        );

        if (!$payload) {
            return response()->json(['message' => 'Actualización ignorada por desconexión pendiente'], 200);
        }

        return response()->json([
            'message' => 'Ubicación actualizada',
            'driver' => $payload,
        ]);
    }

    public function setDriverOffline(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->rol || $user->rol->rol_name !== 'conductor') {
            return response()->json(['error' => 'Solo los conductores pueden cambiar su estado'], 403);
        }

        $this->locationService->setDriverOffline($user->id);

        return response()->json(['message' => 'Conductor marcado como offline']);
    }

    public function requestDisconnect(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->rol || $user->rol->rol_name !== 'conductor') {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $data = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $this->disconnectService->requestDisconnect($user, $data['reason']);

        return response()->json(['message' => 'Solicitud enviada a los administradores.']);
    }

    public function approveDisconnect(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || $user->rol_id !== 1) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $this->disconnectService->approveDisconnect($id);

        return response()->json(['message' => 'Desconexión del conductor aprobada.']);
    }

    public function rejectDisconnect(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || $user->rol_id !== 1) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $this->disconnectService->rejectDisconnect($id);

        return response()->json(['message' => 'Desconexión del conductor rechazada.']);
    }

    public function getNearbyDrivers(GetNearbyDriversRequest $request): JsonResponse
    {
        $data = $request->validated();
        $drivers = $this->locationService->getNearbyDrivers(
            (float) $data['latitude'],
            (float) $data['longitude'],
            (float) ($data['radius'] ?? 10.0)
        );

        return response()->json(['drivers' => $drivers]);
    }

    public function getOnlineDrivers(Request $request): JsonResponse
    {
        $user = $request->user();
        $isAdmin = $user && $user->rol_id === 1;

        $drivers = $this->locationService->getOnlineDrivers($isAdmin);

        return response()->json($drivers);
    }
}
