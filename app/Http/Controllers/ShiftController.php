<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShiftRequest;
use App\Http\Requests\UpdateShiftRequest;
use App\Services\ShiftService;
use App\Models\AuditLog;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    protected ShiftService $shiftService;

    public function __construct(ShiftService $shiftService)
    {
        $this->shiftService = $shiftService;
    }

    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $perPage = $request->query('per_page', 10);
        $status = $request->query('status');

        $shifts = $this->shiftService->getAllShifts($search, $perPage, $status);

        return response()->json($shifts);
    }

    public function store(StoreShiftRequest $request): JsonResponse
    {
        $shift = $this->shiftService->createShift($request->validated());
        AuditLog::record('shift.created', $shift, [], $shift->toArray());
        return response()->json($shift, 201);
    }

    public function show(int $id): JsonResponse
    {
        $shift = $this->shiftService->getShiftById($id);
        return response()->json($shift, 200);
    }

    public function update(UpdateShiftRequest $request, int $id): JsonResponse
    {
        $before = Shift::findOrFail($id)->toArray();
        $shift = $this->shiftService->updateShift($id, $request->validated());
        AuditLog::record('shift.updated', $shift, $before, $shift->toArray());
        return response()->json($shift, 200);
    }

    public function destroy(int $id): JsonResponse
    {
        $before = Shift::findOrFail($id)->toArray();
        $this->shiftService->deleteShift($id);
        AuditLog::record('shift.deleted', $id, $before);
        return response()->json(null, 204);
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $before = Shift::findOrFail($id)->toArray();
        $shift = $this->shiftService->toggleStatus($id);
        AuditLog::record('shift.status_toggled', $shift, $before, $shift->toArray());
        return response()->json([
            'message' => 'Estado del turno actualizado',
            'is_active' => $shift->is_active
        ]);
    }
}
