<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Repositories\RolRepository;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

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

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                throw new \Exception('Las credenciales proporcionadas son inválidas.');
            }
        } catch (JWTException $e) {
            throw new \Exception('Error en la autenticación: ' . $e->getMessage());
        }

        $user = $this->userRepo->findByEmail($email);

        if (!$user->is_active) {
            JWTAuth::invalidate($token);
            throw new \Exception('Su cuenta ha sido desactivada. Contacte al administrador.');
        }

        return $this->respondWithToken($token, $user);
    }

    public function register(array $data)
    {
        try {
            DB::beginTransaction();

            $role = $this->rolRepo->find($data['role_id']);
            if (!$role) {
                throw new \Exception('El rol seleccionado no existe.');
            }

            if ($this->userRepo->findByEmail($data['email'])) {
                throw new \Exception('El correo electrónico ya está registrado.');
            }

            $user = $this->userRepo->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'rol_id' => $data['role_id'],
                'is_active' => true,
            ]);

            DB::commit();

            $token = JWTAuth::fromUser($user);

            return $this->respondWithToken($token, $user);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (JWTException $e) {
            throw new \Exception('No se pudo cerrar sesión: ' . $e->getMessage());
        }
        return true;
    }

    protected function respondWithToken($token, $user = null)
    {
        if ($user && !isset($user->rol)) {
            $user = $this->userRepo->find($user->id);
        }

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->rol->rol_name ?? null,
                'role_id' => $user->rol_id,
                'is_active' => $user->is_active,
            ]
        ];
    }

    public function listUsers(int $perPage, ?string $search, ?int $roleId, ?bool $isActive)
    {
        return $this->userRepo->paginate($perPage, $search, $roleId, $isActive);
    }

    public function createDriver(array $data)
    {
        try {
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
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function toggleUserStatus(int $id)
    {
        try {
            $user = $this->userRepo->find($id);

            if (!$user) {
                throw new \Exception('Usuario no encontrado.');
            }

            $updatedUser = $this->userRepo->toggleStatus($id);
            return $this->userRepo->find($updatedUser->id);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getAvailableRoles()
    {
        return $this->rolRepo->all();
    }

    public function getCurrentUser()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            throw new \Exception('Usuario no autenticado.');
        }

        if (!isset($user->rol)) {
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
}



