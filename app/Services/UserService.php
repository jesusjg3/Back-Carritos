<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\RolRepository;
use Illuminate\Support\Facades\Cache;

class UserService
{
    protected UserRepository $userRepo;
    protected RolRepository $rolRepo;

    public function __construct(UserRepository $userRepo, RolRepository $rolRepo)
    {
        $this->userRepo = $userRepo;
        $this->rolRepo = $rolRepo;
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
        $allowedFields = ['name', 'email', 'rol_id', 'password'];
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

    public function getDashboardStats()
    {
        return Cache::remember('dashboard_stats', 15, function () {
            $driverRole = \App\Models\Rol::where('rol_name', 'conductor')->first();
            $driverRoleId = $driverRole ? $driverRole->id : 3;

            $adminRole = \App\Models\Rol::where('rol_name', 'admin')->first();
            $adminRoleId = $adminRole ? $adminRole->id : 1;

            $passengerRole = \App\Models\Rol::where('rol_name', 'pasajero')->first();
            $passengerRoleId = $passengerRole ? $passengerRole->id : 2;

            return [
                'users' => \App\Models\User::withTrashed()->count(),
                'drivers' => \App\Models\User::withTrashed()->where('rol_id', $driverRoleId)->count(),
                'admins' => \App\Models\User::withTrashed()->where('rol_id', $adminRoleId)->count(),
                'passengers' => \App\Models\User::withTrashed()->where('rol_id', $passengerRoleId)->count(),
                'destinations' => \App\Models\Destination::withTrashed()->count(),
                'trips' => \App\Models\Trip::count(),
                'active' => \App\Models\Trip::whereIn('state_id', [1, 2, 4])->count(),
                'completed' => \App\Models\Trip::where('state_id', 3)->count(),
            ];
        });
    }
}
