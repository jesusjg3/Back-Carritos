<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->validated());
            return response()->json($result, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                $request->input('email'),
                $request->input('password')
            );
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        }
    }

    public function logout(): JsonResponse
    {
        try {
            $this->authService->logout();
            return response()->json(['message' => 'Cierre de sesión exitoso']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Admin Methods

    public function listUsers(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 10);
        $search = $request->query('search');
        $roleId = $request->query('role_id');
        $isActive = $request->has('is_active') ? $request->boolean('is_active') : null;

        $users = $this->authService->listUsers($perPage, $search, $roleId, $isActive);

        return response()->json($users);
    }

    public function storeDriver(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        try {
            $driver = $this->authService->createDriver($request->all());
            return response()->json($driver, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $user = $this->authService->toggleUserStatus($id);
            return response()->json([
                'message' => 'Estado del usuario actualizado',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}



