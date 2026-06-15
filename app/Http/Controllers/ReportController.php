<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function summary(): JsonResponse
    {
        $data = $this->reportService->getSummary();
        return response()->json($data);
    }

    public function topDrivers(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        $limit = $request->integer('limit', 10);
        $data = $this->reportService->getTopDrivers($limit);
        return response()->json($data);
    }

    public function topPassengers(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        $limit = $request->integer('limit', 10);
        $data = $this->reportService->getTopPassengers($limit);
        return response()->json($data);
    }

    public function tripsByDate(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'sometimes|date_format:Y-m-d',
            'end_date' => 'sometimes|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $data = $this->reportService->getTripsByDate($startDate, $endDate);
        return response()->json($data);
    }

    public function ratingsDistribution(): JsonResponse
    {
        $data = $this->reportService->getRatingsDistribution();
        return response()->json($data);
    }

    public function coverageMap(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'sometimes|integer|min:1|max:2000',
        ]);

        $limit = $request->integer('limit', 500);
        $data = $this->reportService->getCoverageMap($limit);
        return response()->json($data);
    }
}
