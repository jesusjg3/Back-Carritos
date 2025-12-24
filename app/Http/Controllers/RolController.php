<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRolRequest;
use App\Http\Requests\UpdateRolRequest;
use App\Services\RolService;
use Illuminate\Http\JsonResponse;

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
        return response()->json($rol, 201);
    }

    public function update(UpdateRolRequest $request, int $id): JsonResponse
    {
        $rol = $this->rolService->updateRol($id, $request->validated());
        return response()->json($rol);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->rolService->deleteRol($id);
        return response()->json(null, 204);
    }
}
