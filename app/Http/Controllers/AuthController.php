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

    /**
     * Registrar un nuevo usuario
     * POST /api/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->validated());
            return response()->json($result, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Iniciar sesión
     * POST /api/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                $request->input('email'),
                $request->input('password')
            );
            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        }
    }

    /**
     * Cerrar sesión
     * POST /api/logout
     */
    public function logout(): JsonResponse
    {
        try {
            $this->authService->logout();
            return response()->json(['message' => 'Cierre de sesión exitoso'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener el perfil del usuario autenticado
     * GET /api/me
     */
    public function me(): JsonResponse
    {
        try {
            $user = $this->authService->getCurrentUser();
            return response()->json($user, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        }
    }

    /**
     * Obtener roles disponibles para registro
     * GET /api/roles
     */
    public function getRoles(): JsonResponse
    {
        try {
            $roles = $this->authService->getAvailableRoles();
            return response()->json(['roles' => $roles], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Verificar disponibilidad de correo electrónico
     * POST /api/check-email
     */
    public function checkEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El formato del correo electrónico es inválido.',
        ]);

        try {
            $email = $request->input('email');
            $user = \App\Models\User::where('email', $email)->first();

            return response()->json([
                'available' => is_null($user),
                'message' => is_null($user) ? 'El correo está disponible' : 'El correo ya está registrado'
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ============ Admin Methods ============

    /**
     * Listar todos los usuarios (solo admin)
     * GET /api/users
     */
    public function listUsers(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 10);
        $search = $request->query('search');
        $roleId = $request->query('role_id');
        $isActive = $request->has('is_active') ? $request->boolean('is_active') : null;

        try {
            $users = $this->authService->listUsers($perPage, $search, $roleId, $isActive);
            return response()->json($users, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Crear un nuevo conductor (solo admin)
     * POST /api/users/drivers
     */
    public function storeDriver(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|min:3',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser válido.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        try {
            $driver = $this->authService->createDriver($request->all());
            return response()->json([
                'message' => 'Conductor creado exitosamente',
                'user' => [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'email' => $driver->email,
                    'role' => $driver->rol->rol_name,
                    'role_id' => $driver->rol_id,
                    'is_active' => $driver->is_active,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Cambiar el estado de un usuario (activar/desactivar)
     * PATCH /api/users/{id}/toggle-status
     */
    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $user = $this->authService->toggleUserStatus($id);
            return response()->json([
                'message' => 'Estado del usuario actualizado exitosamente',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'role' => $user->rol->rol_name,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}



