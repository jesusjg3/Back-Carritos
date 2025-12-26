<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Repositories\RolRepository;
use Illuminate\Support\Facades\Hash;
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
        $user = $this->userRepo->findByEmail($email);

        if (!$user || !Hash::check($password, $user->password)) {
            throw new \Exception('Invalid credentials');
        }

        $user->load('rol');

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->rol->rol_name,
            ]
        ];
    }

    public function register(array $data)
    {
        $user = DB::transaction(function () use ($data) {
            return $this->userRepo->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'rol_id' => $data['role_id'],
            ]);
        });

        $user->load('rol');

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->rol->rol_name ?? 'Unknown',
            ]
        ];
    }


    public function logout(User $user)
    {
        return $user->currentAccessToken()->delete();
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



