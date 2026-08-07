<?php

namespace App\Repositories;

use App\Models\DriverProfile;
use Illuminate\Pagination\LengthAwarePaginator;

class DriverProfileRepository
{
    public function getActiveProfileByUserId(int $userId): ?DriverProfile
    {
        // Status has been removed from migration and replaced with softDeletes.
        // It's sufficient to check if the record exists since deleted_at takes care of inactive ones.
        return DriverProfile::where('user_id', $userId)
            ->with(['vehicle', 'shift'])
            ->first();
    }
    
    public function all($search = null, $perPage = 10, $status = null): LengthAwarePaginator | array
    {
        $query = DriverProfile::withTrashed()->with([
            'user' => fn($q) => $q->select('id', 'name', 'email')->withTrashed(),
            'shift' => fn($q) => $q->select('id', 'name', 'start_time', 'end_time')->withTrashed(),
            'vehicle' => fn($q) => $q->select('id', 'brand', 'model', 'plate')->withTrashed()
        ]);

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'ilike', '%' . $search . '%');
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

        $baseCountQuery = DriverProfile::withTrashed();
        if ($search) {
            $baseCountQuery->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'ilike', '%' . $search . '%');
            });
        }

        $totalInactive = (clone $baseCountQuery)->where('is_active', false)->whereNull('deleted_at')->count();
        $totalDeleted = (clone $baseCountQuery)->whereNotNull('deleted_at')->count();

        $paginator = $query->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END ASC')
                           ->orderBy('id', 'desc')
                           ->paginate($perPage);
        $result = $paginator->toArray();
        $result['total_registrados'] = (clone $baseCountQuery)->count();
        $result['total_inactivos'] = $totalInactive;
        $result['total_eliminados'] = $totalDeleted;

        return $result;
    }

    public function find(int $id)
    {
        $profile = DriverProfile::withTrashed()->findOrFail($id);
        $profile->load([
            'user' => fn($q) => $q->select('id', 'name', 'email')->withTrashed(),
            'shift' => fn($q) => $q->select('id', 'name', 'start_time', 'end_time')->withTrashed(),
            'vehicle' => fn($q) => $q->select('id', 'brand', 'model', 'plate')->withTrashed()
        ]);
        return $profile;
    }

    public function create(array $data)
    {
        $profile = DriverProfile::create($data);
        $profile->load(['user', 'shift', 'vehicle']);
        return $profile;
    }

    public function update(int $id, array $data)
    {
        $profile = $this->find($id);
        $profile->update($data);
        $profile->load(['user', 'shift', 'vehicle']);
        return $profile;
    }

    public function delete(int $id)
    {
        $profile = $this->find($id);
        return $profile->delete();
    }

    public function toggleStatus(int $id)
    {
        $profile = $this->find($id);
        $profile->is_active = !$profile->is_active;
        $profile->save();
        return $profile;
    }
}
