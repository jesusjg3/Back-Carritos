<?php

namespace App\Services;

use App\Repositories\ComplaintRepository;

class ComplaintService
{
    protected ComplaintRepository $complaintRepo;

    public function __construct(ComplaintRepository $complaintRepo)
    {
        $this->complaintRepo = $complaintRepo;
    }

    public function getAllComplaints()
    {
        return $this->complaintRepo->all();
    }

    public function getComplaintById(int $id)
    {
        return $this->complaintRepo->find($id);
    }

    public function createComplaint(array $data, int $userId)
    {
        $data['user_id'] = $userId;
        $data['status'] = 'pending';
        return $this->complaintRepo->create($data);
    }

    public function updateComplaintStatus(int $id, array $data)
    {
        return $this->complaintRepo->update($id, $data);
    }
}
