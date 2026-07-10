<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function listUsers(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 10);
        $search = $request->query('search');
        $roleId = $request->query('role_id');
        $roleName = $request->query('role_name');
        $isActive = $request->has('is_active') ? $request->boolean('is_active') : null;

        $users = $this->userService->listUsers($perPage, $search, $roleId, $isActive, $roleName);

        return response()->json($users);
    }

    public function storeDriver(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,NULL,id,deleted_at,NULL',
            'password' => 'required|string|min:8',
        ]);

        try {
            $driver = $this->userService->createDriver($request->all());
            return response()->json($driver, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function storeAdmin(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,NULL,id,deleted_at,NULL',
            'password' => 'required|string|min:8',
        ]);

        try {
            $admin = $this->userService->createAdmin($request->all());
            return response()->json($admin, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $user = $this->userService->toggleUserStatus($id);
            return response()->json([
                'message' => 'Estado del usuario actualizado',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function updateUser(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id . ',id,deleted_at,NULL',
            'rol_id' => 'sometimes|integer|exists:rols,id',
            'password' => 'sometimes|nullable|string|min:8',
        ]);

        try {
            $user = $this->userService->updateUser($id, $validated);
            return response()->json([
                'message' => 'Usuario actualizado correctamente',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function deleteUser(int $id): JsonResponse
    {
        try {
            $this->userService->deleteUser($id);
            return response()->json([
                'message' => 'Usuario eliminado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function restoreUser(int $id): JsonResponse
    {
        try {
            $user = $this->userService->restoreUser($id);
            return response()->json([
                'message' => 'Usuario restaurado correctamente',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

}
