<?php

namespace App\Services;

use App\Models\User;
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
        if (!$token = auth()->attempt(['email' => $email, 'password' => $password])) {
            throw new \Exception('Invalid credentials');
        }

        return $this->respondWithToken($token);
    }

    public function register(array $data)
    {
        $user = $this->userRepo->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'rol_id' => $data['role_id'],
        ]);

        $token = auth()->login($user);

        return $this->respondWithToken($token);
    }


    public function logout()
    {
        auth()->logout();
        return true;
    }

    protected function respondWithToken($token)
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
        ];
    }

    // Admin Features
    public function listUsers(int $perPage, ?string $search, ?int $roleId, ?bool $isActive)
    {
        return $this->userRepo->paginate($perPage, $search, $roleId, $isActive);
    }

    public function createDriver(array $data)
    {
        // Find conductor role ID
        $driverRole = $this->rolRepo->findByName('conductor');

        return $this->userRepo->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'rol_id' => $driverRole->id,
            'is_active' => true,
        ]);
    }

    public function toggleUserStatus(int $id)
    {
        return $this->userRepo->toggleStatus($id);
    }
}



