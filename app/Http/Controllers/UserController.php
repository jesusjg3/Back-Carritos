<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminRequest;
use App\Http\Requests\StoreDriverRequest;
use App\Http\Requests\UpdateUserRequest;
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
        $status = $request->query('status');

        $users = $this->userService->listUsers($perPage, $search, $roleId, $status, $roleName);

        return response()->json($users);
    }

    public function storeDriver(StoreDriverRequest $request): JsonResponse
    {
        try {
            $driver = $this->userService->createDriver($request->validated());
            return response()->json($driver, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function storeAdmin(StoreAdminRequest $request): JsonResponse
    {
        try {
            $admin = $this->userService->createAdmin($request->validated());
            return response()->json($admin, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $user = $this->userService->toggleUserStatus($id, auth('api')->id());
            return response()->json([
                'message' => 'Estado del usuario actualizado',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function updateUser(UpdateUserRequest $request, int $id): JsonResponse
    {
        try {
            $data = $request->validated();
            // Asegurar que si permissions viene vacío, se respete para poder limpiar los permisos
            if ($request->has('permissions')) {
                $data['permissions'] = $request->input('permissions', []);
            }

            $user = $this->userService->updateUser($id, $data);
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
            $this->userService->deleteUser($id, auth('api')->id());
            return response()->json([
                'message' => 'Usuario eliminado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
