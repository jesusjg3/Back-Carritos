<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreComplaintRequest;
use App\Http\Requests\UpdateComplaintStatusRequest;
use App\Services\ComplaintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ComplaintController extends Controller
{
    protected ComplaintService $complaintService;

    public function __construct(ComplaintService $complaintService)
    {
        $this->complaintService = $complaintService;
    }

    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $perPage = $request->query('per_page', 10);
        $status = $request->query('status');
        $complaints = $this->complaintService->getAllComplaints($search, $perPage, $status);
        return response()->json($complaints, 200);
    }

    public function store(StoreComplaintRequest $request): JsonResponse
    {
        $userId = Auth::id();
        if (!$userId) abort(401, 'No autenticado');
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
