<?php

namespace App\Http\Controllers;

use App\Services\DriverProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DriverProfileController extends Controller
{
    protected DriverProfileService $driverProfileService;

    public function __construct(DriverProfileService $driverProfileService)
    {
        $this->driverProfileService = $driverProfileService;
    }

    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $perPage = $request->query('per_page', 10);
        $status = $request->query('status');
        
        $profiles = $this->driverProfileService->getAllProfiles($search, $perPage, $status);
        return response()->json($profiles, 200);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id', Rule::unique('driver_profiles', 'user_id')->whereNull('deleted_at')],
            'shift_id' => 'required|exists:shifts,id',
            'vehicle_id' => 'required|exists:vehicles,id',
        ]);

        $profile = $this->driverProfileService->createProfile($data);
        return response()->json($profile, 201);
    }

    public function show(int $id): JsonResponse
    {
        $profile = $this->driverProfileService->getProfileById($id);
        return response()->json($profile, 200);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'shift_id' => 'required|exists:shifts,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'is_active' => 'sometimes|boolean',
        ], [
            'shift_id.exists' => 'El horario seleccionado no es válido.',
            'vehicle_id.exists' => 'El vehículo seleccionado no existe.'
        ]);

        $profile = $this->driverProfileService->updateProfile($id, $data);
        return response()->json($profile, 200);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->driverProfileService->deleteProfile($id);
        return response()->json(null, 204);
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $profile = $this->driverProfileService->toggleStatus($id);
        return response()->json([
            'message' => 'Estado de la asignación actualizado',
            'is_active' => $profile->is_active
        ]);
    }
}
