<?php

namespace App\Observers;

use App\Models\Trip;
use App\Services\ReportService;

class TripObserver
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function created(Trip $trip): void
    {
        $this->reportService->broadcastDashboardUpdates();
    }

    public function updated(Trip $trip): void
    {
        $this->reportService->broadcastDashboardUpdates();
    }

    public function deleted(Trip $trip): void
    {
        $this->reportService->broadcastDashboardUpdates();
    }
}
