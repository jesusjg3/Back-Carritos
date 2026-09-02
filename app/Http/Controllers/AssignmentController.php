<?php

namespace App\Http\Controllers;

use App\Services\AssignmentService;
use App\Models\Assignment;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Shift;
use App\Models\Vehicle;
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
            'user_id' => ['required', 'exists:users,id', Rule::unique('assignments', 'user_id')->whereNull('deleted_at')->where('is_active', true)],
            'shift_id' => ['required', Rule::exists('shifts', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'vehicle_id' => ['required', Rule::exists('vehicles', 'id')->whereIn('status', ['active', 'maintenance'])->whereNull('deleted_at')],
            'is_active' => 'sometimes|boolean',
        ]);

        $this->validateAssignmentAvailability($data);

        $assignment = $this->assignmentService->createAssignment($data);
        AuditLog::record('assignment.created', $assignment, [], $assignment->toArray());
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
            'shift_id' => ['required', Rule::exists('shifts', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'vehicle_id' => ['required', Rule::exists('vehicles', 'id')->whereIn('status', ['active', 'maintenance'])->whereNull('deleted_at')],
            'is_active' => 'sometimes|boolean',
        ], [
            'shift_id.exists' => 'El horario seleccionado no es válido.',
            'vehicle_id.exists' => 'El vehículo seleccionado no existe.'
        ]);

        $current = Assignment::findOrFail($id);
        $this->validateAssignmentAvailability(['user_id' => $current->user_id, ...$data], $id);
        $before = $current->toArray();
        $assignment = $this->assignmentService->updateAssignment($id, $data);
        AuditLog::record('assignment.updated', $assignment, $before, $assignment->toArray());
        return response()->json($assignment, 200);
    }

    public function destroy(int $id): JsonResponse
    {
        $before = Assignment::findOrFail($id)->toArray();
        $this->assignmentService->deleteAssignment($id);
        AuditLog::record('assignment.deleted', $id, $before);
        return response()->json(null, 204);
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $before = Assignment::findOrFail($id)->toArray();
        $assignment = $this->assignmentService->toggleStatus($id);
        AuditLog::record('assignment.status_toggled', $assignment, $before, $assignment->toArray());
        return response()->json($assignment, 200);
    }

    private function validateAssignmentAvailability(array $data, ?int $ignoreId = null): void
    {
        $user = User::with('rol')->findOrFail($data['user_id']);
        if ($user->rol?->rol_name !== 'conductor' || !$user->is_active) {
            abort(422, 'Solo se pueden asignar conductores activos.');
        }

        $shift = Shift::findOrFail($data['shift_id']);
        if (!$shift->is_active) {
            abort(422, 'El horario seleccionado está inactivo.');
        }

        $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        if (!in_array($vehicle->status, ['active', 'maintenance'], true)) {
            abort(422, 'El vehículo seleccionado no está disponible.');
        }

        $query = Assignment::query()
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->when($ignoreId, fn ($builder) => $builder->where('id', '<>', $ignoreId));

        if ($query->where('vehicle_id', $vehicle->id)->exists()) {
            abort(422, 'El vehículo ya tiene una asignación activa.');
        }
    }
}
