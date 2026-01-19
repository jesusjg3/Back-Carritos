<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\RolRepository;
use Illuminate\Support\Facades\DB;

class AuthService
{
    protected UserRepository $userRepo;
    protected RolRepository $rolRepo;

    public function __construct(UserRepository $userRepo, RolRepository $rolRepo)
    {
        $this->userRepo = $userRepo;
        $this->rolRepo = $rolRepo;
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

        return $this->respondWithToken($token, $user);
    }

    public function register(array $data)
    {
        $user = $this->userRepo->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'rol_id' => $data['role_id'],
            'is_active' => true,
        ]);

        $token = auth('api')->login($user);

        return $this->respondWithToken($token, $user);
    }

    public function logout()
    {
        auth('api')->logout();
        return true;
    }

    protected function respondWithToken($token, $user = null)
    {
        $user = $user ?? auth('api')->user();

        if ($user && !$user->relationLoaded('rol')) {
            $user = $this->userRepo->find($user->id);
        }

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->rol->rol_name ?? null,
                'role_id' => $user->rol_id,
                'is_active' => $user->is_active,
            ],
        ];
    }

    public function listUsers(int $perPage, ?string $search, ?int $roleId, ?bool $isActive)
    {
        return $this->userRepo->paginate($perPage, $search, $roleId, $isActive);
    }

    public function createDriver(array $data)
    {
        $driverRole = $this->rolRepo->findByName('conductor');

        if (!$driverRole) {
            throw new \Exception('El rol de conductor no existe.');
        }

        if ($this->userRepo->findByEmail($data['email'])) {
            throw new \Exception('El correo electrónico ya está registrado.');
        }

        $user = $this->userRepo->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'rol_id' => $driverRole->id,
            'is_active' => true,
        ]);

        return $this->userRepo->find($user->id);
    }

    public function toggleUserStatus(int $id)
    {
        return $this->userRepo->toggleStatus($id);
    }

    public function updateUser(int $id, array $data)
    {
        $user = $this->userRepo->find($id);

        // Validar que el nuevo email no esté registrado (excepto si es del mismo usuario)
        if (isset($data['email']) && $data['email'] !== $user->email) {
            $emailCheck = $this->checkEmailAvailability($data['email']);
            if (!$emailCheck['available']) {
                throw new \Exception('El correo electrónico ya está registrado.');
            }
        }

        // Si se proporciona un rol_id, validar que exista
        if (isset($data['rol_id'])) {
            $rol = $this->rolRepo->find($data['rol_id']);
            if (!$rol) {
                throw new \Exception('El rol especificado no existe.');
            }
        }

        // Actualizar solo los campos permitidos
        $allowedFields = ['name', 'email', 'rol_id'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        $updatedUser = $this->userRepo->update($id, $updateData);

        return [
            'id' => $updatedUser->id,
            'name' => $updatedUser->name,
            'email' => $updatedUser->email,
            'role' => $updatedUser->rol->rol_name ?? null,
            'role_id' => $updatedUser->rol_id,
            'is_active' => $updatedUser->is_active,
        ];
    }

    public function deleteUser(int $id)
    {
        // No permitir eliminar el usuario actual
        $currentUser = auth('api')->user();
        if ($currentUser && $currentUser->id === $id) {
            throw new \Exception('No puedes eliminar tu propia cuenta.');
        }

        $this->userRepo->delete($id);
        return true;
    }

    public function me()
    {
        $user = auth('api')->user();

        if (!$user) {
            throw new \Exception('Usuario no autenticado.');
        }

        if (!$user->relationLoaded('rol')) {
            $user = $this->userRepo->find($user->id);
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->rol->rol_name ?? null,
            'role_id' => $user->rol_id,
            'is_active' => $user->is_active,
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
        $token = auth('api')->refresh();
        $user = auth('api')->user();

        return $this->respondWithToken($token, $user);
    }
}



