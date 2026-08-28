<?php

namespace App\Observers;

use App\Models\Complaint;
use App\Services\ReportService;

class ComplaintObserver
{
    public function __construct(protected ReportService $reportService)
    {
    }

    public function created(Complaint $complaint): void
    {
        $this->reportService->broadcastDashboardUpdates();
    }

    public function updated(Complaint $complaint): void
    {
        $this->reportService->broadcastDashboardUpdates();
    }

    public function deleted(Complaint $complaint): void
    {
        $this->reportService->broadcastDashboardUpdates();
    }
}
