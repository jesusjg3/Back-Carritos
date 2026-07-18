<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\UserRating;
use Illuminate\Support\Facades\Hash;

class UserRepository
{
    public function all()
    {
        return User::with('rol')->get();
    }

    public function paginate(int $perPage = 10, ?string $search = null, ?int $roleId = null, ?string $status = null, ?string $roleName = null)
    {
        $query = User::with(['rol', 'ratingProfile'])->withTrashed();

        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if ($roleId) {
            $query->where('rol_id', $roleId);
        }

        if ($roleName) {
            $query->whereHas('rol', function ($subQuery) use ($roleName) {
                $subQuery->where('rol_name', $roleName);
            });
        }

        if ($status !== null) {
            if ($status === 'active') {
                $query->where('is_active', true)->whereNull('deleted_at');
            } elseif ($status === 'suspended') {
                $query->whereNotNull('deleted_at');
            } elseif ($status === 'inactive') {
                $query->where('is_active', false)->whereNull('deleted_at');
            }
        }

        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function ($user) {
            $roleName = $user->rol ? strtolower($user->rol->rol_name) : 'pasajero';
            
            $data = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->deleted_at ? false : $user->is_active,
                'created_at' => $user->created_at,
                'deleted_at' => $user->deleted_at,
                'role' => $roleName,
            ];

            if ($roleName === 'conductor') {
                $data['score'] = $user->ratingProfile ? $user->ratingProfile->score : 5.0;
            }

            return $data;
        });

        return $paginator;
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
        return User::create($data);
    }

    public function update($id, array $data)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->update($data);
        return $user;
    }

    public function delete($id)
    {
        return User::withTrashed()->findOrFail($id)->delete();
    }

    public function toggleStatus($id)
    {
        $user = User::with('rol')->withTrashed()->findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();
        return $user;
    }

    public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();
        return $user;
    }

    public function getRatingProfile(int $userId)
    {
        return UserRating::firstOrCreate(
            ['user_id' => $userId],
            ['score' => 5.0, 'rating_count' => 0]
        );
    }

    public function updateRatingProfile(int $userId, float $score, int $ratingCount)
    {
        return UserRating::where('user_id', $userId)->update([
            'score' => $score,
            'rating_count' => $ratingCount,
        ]);
    }
}



