<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\RolRepository;
use Illuminate\Support\Facades\Hash;

class UserService
{
    protected UserRepository $userRepo;
    protected RolRepository $rolRepo;

    public function __construct(UserRepository $userRepo, RolRepository $rolRepo)
    {
        $this->userRepo = $userRepo;
        $this->rolRepo = $rolRepo;
    }

    public function listUsers(int $perPage = 10, ?string $search = null, ?int $roleId = null, ?string $status = null, ?string $roleName = null)
    {
        return $this->userRepo->paginate($perPage, $search, $roleId, $status, $roleName);
    }

    public function createDriver(array $data)
    {
        $driverRole = $this->rolRepo->findByName('conductor');

        if (!$driverRole) {
            throw new \Exception('El rol de conductor no existe.');
        }

        $user = $this->userRepo->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'rol_id' => $driverRole->id,
            'is_active' => true,
        ]);

        return $this->userRepo->find($user->id);
    }

    public function createAdmin(array $data)
    {
        $adminRole = $this->rolRepo->findByName('admin');

        if (!$adminRole) {
            throw new \Exception('El rol de administrador no existe.');
        }

        $user = $this->userRepo->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'rol_id' => $adminRole->id,
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
        $allowedFields = ['name', 'email', 'rol_id', 'password'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (!empty($updateData['password'])) {
            $updateData['password'] = Hash::make($updateData['password']);
        }

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
        $currentUser = auth('api')->user();
        if ($currentUser && $currentUser->id === $id) {
            throw new \Exception('No puedes eliminar tu propia cuenta.');
        }

        $this->userRepo->delete($id);
        return true;
    }

    public function restoreUser(int $id)
    {
        return $this->userRepo->restore($id);
    }
}
