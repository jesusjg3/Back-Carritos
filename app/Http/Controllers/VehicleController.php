<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Services\VehicleService;
use App\Models\AuditLog;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    protected VehicleService $vehicleService;

    public function __construct(VehicleService $vehicleService)
    {
        $this->vehicleService = $vehicleService;
    }

    /**
     * Display a listing of the vehicles.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $perPage = $request->input('per_page', 10);

        $vehicles = $this->vehicleService->getAllVehicles($search, $status, $perPage);

        return response()->json($vehicles);
    }

    /**
     * Store a newly created vehicle in storage.
     */
    public function store(StoreVehicleRequest $request)
    {
        $vehicle = $this->vehicleService->createVehicle($request->validated());
        AuditLog::record('vehicle.created', $vehicle, [], $vehicle->toArray());

        return response()->json([
            'message' => 'Vehículo creado exitosamente.',
            'vehicle' => $vehicle
        ], 201);
    }

    /**
     * Display the specified vehicle.
     */
    public function show(int $id)
    {
        $vehicle = $this->vehicleService->getVehicleById($id);
        return response()->json($vehicle);
    }

    /**
     * Update the specified vehicle in storage.
     */
    public function update(UpdateVehicleRequest $request, int $id)
    {
        $before = Vehicle::findOrFail($id)->toArray();
        $vehicle = $this->vehicleService->updateVehicle($id, $request->validated());
        AuditLog::record('vehicle.updated', $vehicle, $before, $vehicle->toArray());

        return response()->json([
            'message' => 'Vehículo actualizado exitosamente.',
            'vehicle' => $vehicle
        ]);
    }

    /**
     * Remove the specified vehicle from storage (Soft Delete/Hard Delete).
     */
    public function destroy(int $id)
    {
        $before = Vehicle::findOrFail($id)->toArray();
        $this->vehicleService->deleteVehicle($id);
        AuditLog::record('vehicle.deleted', $id, $before);
        return response()->json(['message' => 'Vehículo eliminado exitosamente.']);
    }
}
