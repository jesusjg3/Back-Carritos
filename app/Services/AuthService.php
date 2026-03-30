<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;

class AuthService
{
    protected UserRepository $userRepo;

    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
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



