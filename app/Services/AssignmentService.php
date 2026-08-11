<?php

namespace App\Services;

use App\Repositories\AssignmentRepository;

class AssignmentService
{
    protected AssignmentRepository $assignmentRepo;

    public function __construct(AssignmentRepository $assignmentRepo)
    {
        $this->assignmentRepo = $assignmentRepo;
    }

    public function getAllAssignments($search = null, $perPage = 10, $status = null)
    {
        return $this->assignmentRepo->all($search, $perPage, $status);
    }

    public function getAssignmentById(int $id)
    {
        return $this->assignmentRepo->find($id);
    }

    public function createAssignment(array $data)
    {
        return $this->assignmentRepo->create($data);
    }

    public function updateAssignment(int $id, array $data)
    {
        return $this->assignmentRepo->update($id, $data);
    }

    public function deleteAssignment(int $id)
    {
        return $this->assignmentRepo->delete($id);
    }

    public function toggleStatus(int $id)
    {
        return $this->assignmentRepo->toggleStatus($id);
    }
}
