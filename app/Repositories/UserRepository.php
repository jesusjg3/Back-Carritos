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
        $query = User::withTrashed()->with(['rol', 'permissions', 'ratingProfile', 'assignment.shift']);

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
                if ($roleName === 'common') {
                    $subQuery->whereIn('rol_name', ['conductor', 'pasajero']);
                } else {
                    $subQuery->where('rol_name', $roleName);
                }
            });
        }

        if ($status !== null) {
            if ($status === 'active') {
                $query->where('is_active', true)->whereNull('deleted_at');
            } elseif ($status === 'deleted') {
                $query->whereNotNull('deleted_at');
            } elseif ($status === 'inactive') {
                $query->where('is_active', false)->whereNull('deleted_at');
            }
        }

        $baseCountQuery = User::withTrashed();

        if ($roleId) {
            $baseCountQuery->where('rol_id', $roleId);
        }
        if ($roleName) {
            $baseCountQuery->whereHas('rol', function ($subQuery) use ($roleName) {
                if ($roleName === 'common') {
                    $subQuery->whereIn('rol_name', ['conductor', 'pasajero']);
                } else {
                    $subQuery->where('rol_name', $roleName);
                }
            });
        }

        $counts = (clone $baseCountQuery)->selectRaw('
            COUNT(*) as total_registrados,
            COUNT(CASE WHEN is_active = false AND deleted_at IS NULL THEN 1 END) as total_inactivos,
            COUNT(CASE WHEN deleted_at IS NOT NULL THEN 1 END) as total_eliminados
        ')->first();
        $paginator = $query->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END ASC')
                           ->orderBy('id', 'desc')
                           ->paginate($perPage);

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
                'permissions' => $user->permissions->pluck('name')->values()->all(),
            ];

            if ($roleName === 'conductor') {
                $data['score'] = $user->ratingProfile ? $user->ratingProfile->score : 5.0;
                
                if ($user->assignment && $user->assignment->shift) {
                    $shift = $user->assignment->shift;
                    $now = now()->format('H:i:s');
                    $data['is_in_shift'] = $now >= $shift->start_time && $now <= $shift->end_time;
                    $data['shift_id'] = $shift->id;
                    $data['shift_name'] = $shift->name;
                } else {
                    $data['is_in_shift'] = false;
                    $data['shift_id'] = null;
                    $data['shift_name'] = null;
                }
            }

            return $data;
        });

        $result = $paginator->toArray();
        $result['total_registrados'] = (int) ($counts->total_registrados ?? 0);
        $result['total_inactivos'] = (int) ($counts->total_inactivos ?? 0);
        $result['total_eliminados'] = (int) ($counts->total_eliminados ?? 0);

        return $result;
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
        $user = User::findOrFail($id);
        $user->update($data);
        return $user;
    }

    public function delete($id)
    {
        return User::findOrFail($id)->delete();
    }

    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();
        return $user;
    }

    public function getRatingProfile(int $userId)
    {
        return UserRating::firstOrCreate(
            ['user_id' => $userId],
            ['score' => 5.0, 'rating_count' => 0]
        );
    }

    public function getRatingProfileForUpdate(int $userId)
    {
        $ratingProfile = UserRating::where('user_id', $userId)->lockForUpdate()->first();
        if (!$ratingProfile) {
            $ratingProfile = UserRating::create([
                'user_id' => $userId, 
                'score' => 5.0, 
                'rating_count' => 0
            ]);
        }
        return $ratingProfile;
    }

    public function updateRatingProfile(int $userId, float $score, int $ratingCount)
    {
        return UserRating::where('user_id', $userId)->update([
            'score' => $score,
            'rating_count' => $ratingCount,
        ]);
    }
}

