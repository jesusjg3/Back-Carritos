<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateDriverLocationRequest;
use App\Http\Requests\GetNearbyDriversRequest;
use App\Services\AuthService;
use App\Services\DriverLocationService;
use App\Repositories\UserRepository;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected AuthService $authService;
    protected UserRepository $userRepo;
    protected DriverLocationService $locationService;

    public function __construct(
        AuthService $authService,
        UserRepository $userRepo,
        DriverLocationService $locationService
    ) {
        $this->authService = $authService;
        $this->userRepo = $userRepo;
        $this->locationService = $locationService;
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

    public function me(): JsonResponse
    {
        try {
            $user = $this->authService->me();
            return response()->json($user);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        }
    }

    public function refresh(): JsonResponse
    {
        try {
            $result = $this->authService->refresh();
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 401);
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

    public function updateUser(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'rol_id' => 'sometimes|integer|exists:rols,id',
        ]);

        try {
            $user = $this->authService->updateUser($id, $validated);
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
            $this->authService->deleteUser($id);
            return response()->json([
                'message' => 'Usuario eliminado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Actualizar la ubicación del conductor
     */
    public function updateDriverLocation(UpdateDriverLocationRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();

            $location = $this->locationService->updateLocation(
                $user,
                $request->latitude,
                $request->longitude
            );

            return response()->json([
                'message' => 'Ubicación actualizada correctamente',
                'location' => [
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'updated_at' => $location->last_update,
                ]
            ]);
        } catch (\Exception $e) {
            $code = $e->getCode();
            return response()->json(['error' => $e->getMessage()], is_int($code) && $code >= 100 && $code < 600 ? $code : 500);
        }
    }

    /**
     * Obtener conductores cercanos
     */
    public function getNearbyDrivers(GetNearbyDriversRequest $request): JsonResponse
    {
        try {
            $drivers = $this->locationService->getNearbyDrivers(
                $request->latitude,
                $request->longitude,
                $request->input('radius', 5)
            );

            return response()->json([
                'drivers' => $drivers,
                'count' => $drivers->count(),
                'search_center' => [
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                ],
                'radius_km' => $request->input('radius', 5),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
