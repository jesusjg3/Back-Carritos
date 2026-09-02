<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminRequest;
use App\Http\Requests\StoreDriverRequest;
use App\Http\Requests\StorePassengerRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UpdateAdminRequest;
use App\Services\UserService;
use App\Models\AuditLog;
use App\Models\User;
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

        $actor = auth('api')->user();
        $canManageAdmins = $actor?->id === 1 || $actor?->hasPermission('manage_admins');
        if (!$canManageAdmins) {
            $roleName = in_array($roleName, ['conductor', 'pasajero'], true) ? $roleName : 'common';
        }

        $users = $this->userService->listUsers($perPage, $search, $roleId, $status, $roleName);

        return response()->json($users);
    }

    public function listAdmins(Request $request): JsonResponse
    {
        $users = $this->userService->listUsers(
            min(max($request->integer('per_page', 10), 1), 100),
            $request->query('search'),
            null,
            $request->query('status'),
            'admin'
        );

        return response()->json($users);
    }

    public function listDashboardDrivers(Request $request): JsonResponse
    {
        $users = $this->userService->listUsers(
            min(max($request->integer('per_page', 1000), 1), 1000),
            null,
            null,
            null,
            'conductor'
        );

        return response()->json($users);
    }

    public function storeDriver(StoreDriverRequest $request): JsonResponse
    {
        try {
            $driver = $this->userService->createDriver($request->validated());
            AuditLog::record('driver.created', $driver, [], $driver->only(['id', 'name', 'email', 'rol_id', 'is_active']));
            return response()->json($driver, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function storeAdmin(StoreAdminRequest $request): JsonResponse
    {
        try {
            $admin = $this->userService->createAdmin($request->validated());
            AuditLog::record('admin.created', $admin, [], $admin->only(['id', 'name', 'email', 'rol_id', 'is_active']));
            return response()->json($admin, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function storePassenger(StorePassengerRequest $request): JsonResponse
    {
        try {
            $passenger = $this->userService->createPassenger($request->validated());
            AuditLog::record('passenger.created', $passenger, [], $passenger->only(['id', 'name', 'email', 'rol_id', 'is_active']));
            return response()->json($passenger, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $beforeUser = User::findOrFail($id);
            $before = $beforeUser->only(['id', 'name', 'email', 'rol_id', 'is_active']);
            $user = $this->userService->toggleUserStatus($id, auth('api')->id());
            AuditLog::record('user.status_toggled', $beforeUser, $before, ['is_active' => $user->is_active]);
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
            $beforeUser = User::findOrFail($id);
            $before = $beforeUser->only(['id', 'name', 'email', 'rol_id', 'is_active']);
            $data = $request->validated();
            $user = $this->userService->updateUser($id, $data);
            $after = $data;
            unset($after['password']);
            AuditLog::record('user.updated', $beforeUser, $before, $after);
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
            $beforeUser = User::findOrFail($id);
            $before = $beforeUser->only(['id', 'name', 'email', 'rol_id', 'is_active']);
            $this->userService->deleteUser($id, auth('api')->id());
            AuditLog::record('user.deleted', $beforeUser, $before);
            return response()->json([
                'message' => 'Usuario eliminado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function updateAdmin(UpdateAdminRequest $request, int $id): JsonResponse
    {
        try {
            $beforeUser = User::with('permissions')->findOrFail($id);
            $beforeValues = $beforeUser->only(['id', 'name', 'email', 'rol_id', 'is_active']);
            $beforeValues['permissions'] = $beforeUser->permissions->pluck('name')->values()->all();
            $admin = $this->userService->updateAdmin($id, $request->validated());
            $after = $request->validated();
            unset($after['password']);
            AuditLog::record('admin.updated', $beforeUser, $beforeValues, $after);

            return response()->json(['message' => 'Administrador actualizado correctamente', 'user' => $admin]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function toggleAdminStatus(int $id): JsonResponse
    {
        try {
            $beforeUser = User::findOrFail($id);
            $before = $beforeUser->only(['id', 'name', 'email', 'rol_id', 'is_active']);
            $user = $this->userService->toggleAdminStatus($id, auth('api')->id());
            AuditLog::record('admin.status_toggled', $beforeUser, $before, ['is_active' => $user->is_active]);
            return response()->json(['message' => 'Estado del administrador actualizado', 'user' => $user]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function deleteAdmin(int $id): JsonResponse
    {
        try {
            $beforeUser = User::findOrFail($id);
            $before = $beforeUser->only(['id', 'name', 'email', 'rol_id', 'is_active']);
            $this->userService->deleteAdmin($id, auth('api')->id());
            AuditLog::record('admin.deleted', $beforeUser, $before);
            return response()->json(['message' => 'Administrador eliminado correctamente']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
