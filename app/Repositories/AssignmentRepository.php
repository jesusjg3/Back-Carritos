<?php

namespace App\Repositories;

use App\Models\Assignment;
use Illuminate\Pagination\LengthAwarePaginator;

class AssignmentRepository
{
    public function getActiveAssignmentByUserId(int $userId): ?Assignment
    {
        return Assignment::where('user_id', $userId)
            ->with(['vehicle', 'shift', 'events' => function ($query) {
                $now = now();
                $query->where('start_date', '<=', $now)
                      ->where('end_date', '>=', $now);
            }])
            ->first();
    }
    
    public function all($search = null, $perPage = 10, $status = null): LengthAwarePaginator | array
    {
        $query = Assignment::withTrashed()->with([
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

        $baseCountQuery = Assignment::withTrashed();

        $counts = (clone $baseCountQuery)->selectRaw('
            COUNT(*) as total_registrados,
            COUNT(CASE WHEN is_active = false AND deleted_at IS NULL THEN 1 END) as total_inactivos,
            COUNT(CASE WHEN deleted_at IS NOT NULL THEN 1 END) as total_eliminados
        ')->first();

        $paginator = $query->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END ASC')
                           ->orderBy('id', 'desc')
                           ->paginate($perPage);
        $result = $paginator->toArray();
        $result['total_registrados'] = (int) ($counts->total_registrados ?? 0);
        $result['total_inactivos'] = (int) ($counts->total_inactivos ?? 0);
        $result['total_eliminados'] = (int) ($counts->total_eliminados ?? 0);

        return $result;
    }

    public function find(int $id): ?Assignment
    {
        return Assignment::withTrashed()->with([
            'user' => fn($q) => $q->select('id', 'name', 'email')->withTrashed(),
            'shift' => fn($q) => $q->select('id', 'name', 'start_time', 'end_time')->withTrashed(),
            'vehicle' => fn($q) => $q->select('id', 'brand', 'model', 'plate')->withTrashed()
        ])->findOrFail($id);
    }

    public function create(array $data): Assignment
    {
        return Assignment::create($data);
    }

    public function update(int $id, array $data): Assignment
    {
        $assignment = Assignment::withTrashed()->findOrFail($id);
        $assignment->update($data);
        return $assignment;
    }

    public function delete(int $id): bool
    {
        $assignment = Assignment::withTrashed()->findOrFail($id);
        return $assignment->delete();
    }

    public function toggleStatus(int $id): Assignment
    {
        $assignment = Assignment::withTrashed()->findOrFail($id);
        $assignment->is_active = !$assignment->is_active;
        $assignment->save();
        return $assignment;
    }
}
