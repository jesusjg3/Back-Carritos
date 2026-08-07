<?php

namespace App\Repositories;

use App\Models\Complaint;

class ComplaintRepository
{
    public function all($perPage = 10, $status = null)
    {
        $query = Complaint::with([
            'user' => fn($q) => $q->select('id', 'name'),
            'trip' => fn($q) => $q->select('id')
        ])->orderBy('created_at', 'desc');

        $baseCountQuery = clone $query;

        if ($status) {
            $query->where('status', $status);
        }

        $totalPending = (clone $baseCountQuery)->where('status', 'pending')->count();
        $totalResolved = (clone $baseCountQuery)->where('status', 'resolved')->count();
        $totalDismissed = (clone $baseCountQuery)->where('status', 'dismissed')->count();

        $paginator = $query->paginate($perPage);
        $result = $paginator->toArray();
        $result['total_pending'] = $totalPending;
        $result['total_resolved'] = $totalResolved;
        $result['total_dismissed'] = $totalDismissed;

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
        $complaint = $this->find($id);
        $complaint->update($data);
        return $complaint;
    }
}
