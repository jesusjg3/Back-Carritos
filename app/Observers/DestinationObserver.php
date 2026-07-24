<?php

namespace App\Observers;

use App\Models\Destination;
use App\Services\ReportService;

class DestinationObserver
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function created(Destination $destination): void
    {
        $this->reportService->broadcastDashboardUpdates();
    }

    public function updated(Destination $destination): void
    {
        $this->reportService->broadcastDashboardUpdates();
    }

    public function deleted(Destination $destination): void
    {
        $this->reportService->broadcastDashboardUpdates();
    }
}
