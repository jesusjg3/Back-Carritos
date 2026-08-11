<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            Log::error('Register error: ' . $e->getMessage());
            return response()->json(['error' => 'Ocurrió un error al registrar el usuario.'], 400);
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
            Log::error('Logout error: ' . $e->getMessage());
            return response()->json(['error' => 'Ocurrió un error al cerrar sesión.'], 500);
        }
    }

    public function me(): JsonResponse
    {
        try {
            $user = $this->authService->me();
            return response()->json($user);
        } catch (\Exception $e) {
            Log::error('Me error: ' . $e->getMessage());
            return response()->json(['error' => 'No autorizado.'], 401);
        }
    }

    public function refresh(): JsonResponse
    {
        try {
            $result = $this->authService->refresh();
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Refresh error: ' . $e->getMessage());
            return response()->json(['error' => 'No se pudo refrescar el token.'], 401);
        }
    }

    public function checkEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $result = $this->authService->checkEmailAvailability($request->input('email'));

        return response()->json($result);
    }


}
