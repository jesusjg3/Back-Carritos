<?php

namespace App\Repositories;

use App\Models\Complaint;

class ComplaintRepository
{
    public function all($search = null, $perPage = 10, $status = null)
    {
        $query = Complaint::with([
            'user' => fn($q) => $q->select('id', 'name'),
            'trip' => fn($q) => $q->select('id')
        ])->orderBy('created_at', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'ilike', '%' . $search . '%')
                  ->orWhere('description', 'ilike', '%' . $search . '%')
                  ->orWhereHas('user', function ($q2) use ($search) {
                      $q2->where('name', 'ilike', '%' . $search . '%');
                  });
            });
        }

        $baseCountQuery = Complaint::query();

        if ($status) {
            $query->where('status', $status);
        }

        $counts = (clone $baseCountQuery)->selectRaw('
            COUNT(*) as total_registrados,
            COUNT(CASE WHEN status = \'pending\' THEN 1 END) as total_pending,
            COUNT(CASE WHEN status = \'resolved\' THEN 1 END) as total_resolved,
            COUNT(CASE WHEN status = \'dismissed\' THEN 1 END) as total_dismissed
        ')->first();

        $paginator = $query->paginate($perPage);
        $result = $paginator->toArray();
        $result['total_registrados'] = (int) ($counts->total_registrados ?? 0);
        $result['total_pending'] = (int) ($counts->total_pending ?? 0);
        $result['total_resolved'] = (int) ($counts->total_resolved ?? 0);
        $result['total_dismissed'] = (int) ($counts->total_dismissed ?? 0);

        return $result;
    }

    public function find($id)
    {
        return Complaint::with(['user', 'trip'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return Complaint::create($data);
    }

    public function update($id, array $data)
    {
        $complaint = Complaint::findOrFail($id);
        $complaint->update($data);
        return $complaint;
    }
}
