<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\AssignmentRepository;
use App\Models\Rol;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    protected UserRepository $userRepo;
    protected AssignmentRepository $assignmentRepo;
    protected LocationService $locationService;

    public function __construct(UserRepository $userRepo, AssignmentRepository $assignmentRepo, LocationService $locationService)
    {
        $this->userRepo = $userRepo;
        $this->assignmentRepo = $assignmentRepo;
        $this->locationService = $locationService;
    }

    public function login(string $email, string $password)
    {
        $credentials = ['email' => $email, 'password' => $password];

        if (!$token = auth('api')->attempt($credentials)) {
            throw new \Exception('Las credenciales proporcionadas son inválidas.');
        }

        $user = auth('api')->user();

        if (!$user->is_active) {
            auth('api')->logout();
            throw new \Exception('Su cuenta ha sido desactivada.');
        }

        $assignment = null;
        // Validate driver constraints
        if ($user->rol && $user->rol->rol_name === 'conductor') {
            $assignment = $this->assignmentRepo->getActiveAssignmentByUserId($user->id);

            if (!$assignment) {
                auth('api')->logout();
                throw new \Exception('No tienes un perfil de conductor asignado.');
            }

            if (!$assignment->is_active) {
                auth('api')->logout();
                throw new \Exception('Tu perfil de conductor se encuentra inactivo.');
            }

            if (!$assignment->shift) {
                auth('api')->logout();
                throw new \Exception('No tienes un horario asignado.');
            }

            // Check if shift is active
            if ($assignment->shift && !$assignment->shift->is_active) {
                auth('api')->logout();
                throw new \Exception('Tu horario asignado se encuentra inactivo.');
            }

            if ($assignment->vehicle && $assignment->vehicle->status === 'inactive') {
                auth('api')->logout();
                throw new \Exception('Tu vehículo asignado se encuentra inactivo.');
            }

            // We do NOT block login if vehicle is in maintenance anymore
            // (Handled by respondWithToken)

            $hasActiveEvent = $assignment->events->isNotEmpty();

            if ($assignment->shift && !$hasActiveEvent) {
                $now = \Carbon\Carbon::now();
                $startTime = \Carbon\Carbon::parse($assignment->shift->start_time);
                $endTime = \Carbon\Carbon::parse($assignment->shift->end_time);

                if ($endTime->lessThan($startTime)) {
                    // Shift spans across midnight
                    if (!$now->between($startTime, $now->copy()->endOfDay()) && 
                        !$now->between($now->copy()->startOfDay(), $endTime)) {
                        auth('api')->logout();
                        throw new \Exception('No te encuentras dentro de tu horario asignado.');
                    }
                } else {
                    if (!$now->between($startTime, $endTime)) {
                        auth('api')->logout();
                        throw new \Exception('No te encuentras dentro de tu horario asignado.');
                    }
                }
            }
        }

        $response = $this->respondWithToken($token, $user, $assignment);

        return $response;
    }

    public function register(array $data)
    {
        $passengerRoleId = Rol::where('rol_name', 'pasajero')->value('id');

        if (!$passengerRoleId) {
            throw new \RuntimeException('El rol de pasajero no está configurado.');
        }

        $user = $this->userRepo->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            // El registro público siempre crea pasajeros. Los roles internos
            // se asignan únicamente desde el panel administrativo protegido.
            'rol_id' => $passengerRoleId,
            'is_active' => true,
        ]);

        $token = auth('api')->login($user);

        return $this->respondWithToken($token, $user);
    }

    public function logout()
    {
        $user = auth('api')->user();
        if ($user && $user->rol && $user->rol->rol_name === 'conductor') {
            $this->locationService->setDriverOffline($user->id);
        }
        
        auth('api')->logout();
        return true;
    }

    protected function respondWithToken($token, $user = null, $assignment = null)
    {
        $user = $user ?? auth('api')->user();

        if ($user && !$user->relationLoaded('rol')) {
            $user = $this->userRepo->find($user->id);
        }
        
        $isMaintenance = false;
        if ($user->rol && $user->rol->rol_name === 'conductor') {
            $assignment = $assignment ?? $this->assignmentRepo->getActiveAssignmentByUserId($user->id);
            if ($assignment && $assignment->vehicle && $assignment->vehicle->status === 'maintenance') {
                $isMaintenance = true;
            }
        }

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'vehicle_maintenance' => $isMaintenance,
            'message' => $isMaintenance ? 'Tu vehículo asignado está en mantenimiento. Podrás ingresar para realizar labores alternas.' : null,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->rol->rol_name ?? null,
                'role_id' => $user->rol_id,
                'is_active' => $user->is_active,
                'permissions' => $user->permissions->pluck('name')->toArray(),
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'vehicle_maintenance' => $isMaintenance,
            ],
        ];
    }

    public function me()
    {
        $user = auth('api')->user();

        if (!$user) {
            throw new \Exception('Usuario no autenticado.');
        }

        if (!$user->is_active) {
            throw new \Exception('Su cuenta ha sido desactivada.');
        }

        if (!$user->relationLoaded('rol')) {
            $user = $this->userRepo->find($user->id);
        }
        
        $isMaintenance = false;
        if ($user->rol && $user->rol->rol_name === 'conductor') {
            $assignment = $this->assignmentRepo->getActiveAssignmentByUserId($user->id);
            if ($assignment && $assignment->vehicle && $assignment->vehicle->status === 'maintenance') {
                $isMaintenance = true;
            }
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->rol->rol_name ?? null,
            'role_id' => $user->rol_id,
            'is_active' => $user->is_active,
            'permissions' => $user->permissions->pluck('name')->toArray(),
            'vehicle_maintenance' => $isMaintenance,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    public function checkEmailAvailability(string $email)
    {
        $user = $this->userRepo->findByEmail($email);

        return [
            'available' => is_null($user),
            'message' => is_null($user) ? 'El correo está disponible' : 'El correo ya está registrado',
        ];
    }

    public function refresh()
    {
        $user = auth('api')->user();

        if (!$user || !$user->is_active) {
            throw new \Exception('Su cuenta ha sido desactivada.');
        }

        $token = auth('api')->refresh();

        return $this->respondWithToken($token, $user);
    }
}
