<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRepository
{
    public function all()
    {
        return User::with('rol')->get();
    }

    public function paginate(int $perPage = 10, ?string $search = null, ?int $roleId = null, ?bool $isActive = null)
    {
        $query = User::with('rol')->withTrashed();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($roleId) {
            $query->where('rol_id', $roleId);
        }

        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }

        return $query->paginate($perPage);
    }

    public function find($id)
    {
        return User::with('rol')->findOrFail($id);
    }

    public function findByEmail($email)
    {
        return User::where('email', $email)->with('rol')->first();
    }

    public function create(array $data)
    {
        $data["password"] = Hash::make($data["password"]);
        return User::create($data);
    }

    public function update($id, array $data)
    {
        $user = User::findOrFail($id);

        if (!empty($data["password"])) {
            $data["password"] = Hash::make($data["password"]);
        }

        $user->update($data);
        return $user;
    }

    public function delete($id)
    {
        return User::findOrFail($id)->delete();
    }

    public function toggleStatus($id)
    {
        $user = User::with('rol')->findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();
        return $user;
    }
}



