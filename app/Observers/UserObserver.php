<?php

namespace App\Observers;

use App\Models\User;
use App\Services\ReportService;

class UserObserver
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function created(User $user): void
    {
        $this->reportService->broadcastDashboardUpdates();
    }

    public function updated(User $user): void
    {
        $this->reportService->broadcastDashboardUpdates();
    }

    public function deleted(User $user): void
    {
        $this->reportService->broadcastDashboardUpdates();
    }
}
