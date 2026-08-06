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
    
    public function all($search = null, $perPage = 10): LengthAwarePaginator
    {
        $query = DriverProfile::with(['user', 'shift', 'vehicle']);

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'ilike', '%' . $search . '%');
            });
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function find(int $id)
    {
        $profile = DriverProfile::findOrFail($id);
        $profile->load(['user', 'shift', 'vehicle']);
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
