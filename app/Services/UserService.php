<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\RolRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

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
            'is_active' => $data['is_active'] ?? true,
        ]);

        return $this->userRepo->find($user->id);
    }

    public function createPassenger(array $data)
    {
        $passengerRole = $this->rolRepo->findByName('pasajero');

        if (!$passengerRole) {
            throw new \Exception('El rol de pasajero no existe.');
        }

        $user = $this->userRepo->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'rol_id' => $passengerRole->id,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return $this->userRepo->find($user->id);
    }

    public function createAdmin(array $data)
    {
        return DB::transaction(function () use ($data) {
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

            if (isset($data['permissions']) && is_array($data['permissions'])) {
                $user->permissions()->sync($data['permissions']);
            }

            return $this->userRepo->find($user->id);
        });
    }

    public function toggleUserStatus(int $id, ?int $actingUserId = null)
    {
        if ($actingUserId === $id) {
            throw new \Exception('No puedes desactivar tu propia cuenta.');
        }
        if ($id === 1) {
            throw new \Exception('No se puede alterar el estado del Super Administrador.');
        }
        $user = $this->userRepo->find($id);
        if ($user->rol?->rol_name === 'admin') {
            throw new \Exception('Las cuentas administrativas requieren permisos de administrador.');
        }
        return $this->userRepo->toggleStatus($id);
    }

    public function updateUser(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $target = $this->userRepo->find($id);
            if ($target->rol?->rol_name === 'admin') {
                throw new \Exception('Las cuentas administrativas requieren permisos de administrador.');
            }

            $allowedFields = ['name', 'email', 'password', 'is_active'];
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
                'permissions' => []
            ];
        });
    }

    public function updateAdmin(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            if ($id === 1) {
                throw new \Exception('No se puede modificar la cuenta del Super Administrador.');
            }

            $admin = $this->userRepo->find($id);
            if ($admin->rol?->rol_name !== 'admin') {
                throw new \Exception('La cuenta seleccionada no es administrativa.');
            }

            $updateData = array_intersect_key($data, array_flip(['name', 'email', 'password', 'is_active']));
            if (!empty($updateData['password'])) {
                $updateData['password'] = Hash::make($updateData['password']);
            }

            $updatedAdmin = $this->userRepo->update($id, $updateData);
            if (array_key_exists('permissions', $data)) {
                $updatedAdmin->permissions()->sync($data['permissions'] ?? []);
            }

            $updatedAdmin->load(['rol', 'permissions']);
            return [
                'id' => $updatedAdmin->id,
                'name' => $updatedAdmin->name,
                'email' => $updatedAdmin->email,
                'role' => $updatedAdmin->rol->rol_name ?? null,
                'role_id' => $updatedAdmin->rol_id,
                'is_active' => $updatedAdmin->is_active,
                'permissions' => $updatedAdmin->permissions->pluck('name')->values()->all(),
            ];
        });
    }

    public function toggleAdminStatus(int $id, ?int $actingUserId = null)
    {
        if ($actingUserId === $id || $id === 1) {
            throw new \Exception('No se puede alterar esta cuenta administrativa.');
        }

        $admin = $this->userRepo->find($id);
        if ($admin->rol?->rol_name !== 'admin') {
            throw new \Exception('La cuenta seleccionada no es administrativa.');
        }

        return $this->userRepo->toggleStatus($id);
    }

    public function deleteAdmin(int $id, ?int $actingUserId = null)
    {
        if ($actingUserId === $id || $id === 1) {
            throw new \Exception('No se puede eliminar esta cuenta administrativa.');
        }

        $admin = $this->userRepo->find($id);
        if ($admin->rol?->rol_name !== 'admin') {
            throw new \Exception('La cuenta seleccionada no es administrativa.');
        }

        return $this->userRepo->delete($id);
    }

    public function deleteUser(int $id, ?int $actingUserId = null)
    {
        if ($actingUserId === $id) {
            throw new \Exception('No puedes eliminar tu propia cuenta.');
        }
        if ($id === 1) {
            throw new \Exception('No se puede eliminar la cuenta del Super Administrador.');
        }

        $user = $this->userRepo->find($id);
        if ($user->rol?->rol_name === 'admin') {
            throw new \Exception('Las cuentas administrativas requieren permisos de administrador.');
        }
        if ($user->assignment()->exists()) {
            throw new \Exception('No se puede eliminar el usuario porque tiene una asignación de conductor.');
        }

        $this->userRepo->delete($id);
        return true;
    }
}
