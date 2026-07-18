<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
{
    /**
     * Display a listing of the vehicles.
     */
    public function index(Request $request)
    {
        $query = Vehicle::query();

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('plate', 'ilike', '%' . $search . '%')
                  ->orWhere('model', 'ilike', '%' . $search . '%')
                  ->orWhere('brand', 'ilike', '%' . $search . '%');
            });
        }

        if ($request->has('is_active') && $request->is_active !== null && $request->is_active !== 'null') {
            $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        $perPage = $request->input('per_page', 10);
        $vehicles = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($vehicles);
    }

    /**
     * Store a newly created vehicle in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'brand' => 'required|string|max:50',
            'model' => 'required|string|max:50',
            'plate' => 'required|string|max:20|unique:vehicles,plate',
            'color' => 'required|string|max:30',
            'capacity' => 'required|integer|min:1|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vehicle = Vehicle::create([
            'brand' => $request->brand,
            'model' => $request->model,
            'plate' => $request->plate,
            'color' => $request->color,
            'capacity' => $request->capacity,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Vehículo creado exitosamente.',
            'vehicle' => $vehicle
        ], 201);
    }

    /**
     * Display the specified vehicle.
     */
    public function show($id)
    {
        $vehicle = Vehicle::find($id);
        
        if (!$vehicle) {
            return response()->json(['message' => 'Vehículo no encontrado.'], 404);
        }

        return response()->json($vehicle);
    }

    /**
     * Update the specified vehicle in storage.
     */
    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::find($id);

        if (!$vehicle) {
            return response()->json(['message' => 'Vehículo no encontrado.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'brand' => 'sometimes|required|string|max:50',
            'model' => 'sometimes|required|string|max:50',
            'plate' => 'sometimes|required|string|max:20|unique:vehicles,plate,' . $vehicle->id,
            'color' => 'sometimes|required|string|max:30',
            'capacity' => 'sometimes|required|integer|min:1|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vehicle->update($request->all());

        return response()->json([
            'message' => 'Vehículo actualizado exitosamente.',
            'vehicle' => $vehicle
        ]);
    }

    /**
     * Remove the specified vehicle from storage (Soft Delete/Hard Delete).
     */
    public function destroy($id)
    {
        $vehicle = Vehicle::find($id);

        if (!$vehicle) {
            return response()->json(['message' => 'Vehículo no encontrado.'], 404);
        }

        $vehicle->delete();

        return response()->json(['message' => 'Vehículo eliminado exitosamente.']);
    }

    /**
     * Toggle the active status of the vehicle.
     */
    public function toggleStatus($id)
    {
        $vehicle = Vehicle::find($id);

        if (!$vehicle) {
            return response()->json(['message' => 'Vehículo no encontrado.'], 404);
        }

        $vehicle->is_active = !$vehicle->is_active;
        $vehicle->save();

        return response()->json([
            'message' => 'Estado del vehículo actualizado.',
            'is_active' => $vehicle->is_active
        ]);
    }
}
