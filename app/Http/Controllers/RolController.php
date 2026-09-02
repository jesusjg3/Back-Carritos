<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRolRequest;
use App\Http\Requests\UpdateRolRequest;
use App\Services\RolService;
use Illuminate\Http\JsonResponse;
use App\Models\AuditLog;
use App\Models\Rol;

class RolController extends Controller
{
    protected RolService $rolService;

    public function __construct(RolService $rolService)
    {
        $this->rolService = $rolService;
    }

    public function index(): JsonResponse
    {
        $roles = $this->rolService->getAllRoles();
        return response()->json($roles);
    }

    public function show(int $id): JsonResponse
    {
        $rol = $this->rolService->getRolById($id);
        return response()->json($rol);
    }

    public function store(StoreRolRequest $request): JsonResponse
    {
        $rol = $this->rolService->createRol($request->validated());
        AuditLog::record('role.created', $rol, [], $rol->toArray());
        return response()->json($rol, 201);
    }

    public function update(UpdateRolRequest $request, int $id): JsonResponse
    {
        $before = Rol::findOrFail($id)->toArray();
        $rol = $this->rolService->updateRol($id, $request->validated());
        AuditLog::record('role.updated', $rol, $before, $rol->toArray());
        return response()->json($rol);
    }

    public function destroy(int $id): JsonResponse
    {
        $before = Rol::findOrFail($id)->toArray();
        $this->rolService->deleteRol($id);
        AuditLog::record('role.deleted', $id, $before);
        return response()->json(null, 204);
    }
}


