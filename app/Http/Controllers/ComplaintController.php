<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreComplaintRequest;
use App\Http\Requests\UpdateComplaintStatusRequest;
use App\Services\ComplaintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ComplaintController extends Controller
{
    protected ComplaintService $complaintService;

    public function __construct(ComplaintService $complaintService)
    {
        $this->complaintService = $complaintService;
    }

    public function index(): JsonResponse
    {
        $complaints = $this->complaintService->getAllComplaints();
        return response()->json($complaints, 200);
    }

    public function store(StoreComplaintRequest $request): JsonResponse
    {
        $userId = Auth::id() ?? 1;
        $complaint = $this->complaintService->createComplaint($request->validated(), $userId);
        return response()->json($complaint, 201);
    }

    public function show(int $id): JsonResponse
    {
        $complaint = $this->complaintService->getComplaintById($id);
        return response()->json($complaint, 200);
    }

    public function updateStatus(UpdateComplaintStatusRequest $request, int $id): JsonResponse
    {
        $complaint = $this->complaintService->updateComplaintStatus($id, $request->validated());
        return response()->json($complaint, 200);
    }
}
