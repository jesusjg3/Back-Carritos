<?php

namespace App\Http\Controllers;

use App\Services\AssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssignmentController extends Controller
{
    protected AssignmentService $assignmentService;

    public function __construct(AssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $perPage = $request->query('per_page', 10);
        $status = $request->query('status');
        
        $assignments = $this->assignmentService->getAllAssignments($search, $perPage, $status);
        return response()->json($assignments, 200);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id', Rule::unique('assignments', 'user_id')->whereNull('deleted_at')],
            'shift_id' => 'required|exists:shifts,id',
            'vehicle_id' => 'required|exists:vehicles,id',
        ]);

        $assignment = $this->assignmentService->createAssignment($data);
        return response()->json($assignment, 201);
    }

    public function show(int $id): JsonResponse
    {
        $assignment = $this->assignmentService->getAssignmentById($id);
        return response()->json($assignment, 200);
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

        $assignment = $this->assignmentService->updateAssignment($id, $data);
        return response()->json($assignment, 200);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->assignmentService->deleteAssignment($id);
        return response()->json(null, 204);
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $assignment = $this->assignmentService->toggleStatus($id);
        return response()->json($assignment, 200);
    }
}
