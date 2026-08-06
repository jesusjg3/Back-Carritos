<?php

namespace App\Repositories;

use App\Models\Complaint;

class ComplaintRepository
{
    public function all()
    {
        return Complaint::with(['user', 'trip'])->orderBy('created_at', 'desc')->get();
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
