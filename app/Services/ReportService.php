<?php

namespace App\Services;

use App\Repositories\ReportRepository;
use Illuminate\Support\Facades\Cache;
use App\Events\DashboardStatsUpdated;

class ReportService
{
    protected ReportRepository $reportRepo;

    public function __construct(ReportRepository $reportRepo)
    {
        $this->reportRepo = $reportRepo;
    }

    public function getDriversSummary(?string $search = null, ?int $perPage = null, ?string $startDate = null, ?string $endDate = null)
    {
        return $this->reportRepo->getDriversSummary($search, $perPage, $startDate, $endDate);
    }

    /**
     * Resumen de demanda por destinos (frecuencia de viajes completados).
     */
    public function getDestinationsSummary(?string $startDate = null, ?string $endDate = null): array
    {
        $totalCompleted = $this->reportRepo->getCompletedTripsCount($startDate, $endDate);
        $safeTotalCompleted = max($totalCompleted, 1);

        return $this->reportRepo->getDestinationsSummary($safeTotalCompleted, $startDate, $endDate);
    }

    /**
     * Resumen de viajes completados por hora del día (0-23).
     */
    public function getHourlySummary(?string $startDate = null, ?string $endDate = null): array
    {
        return $this->reportRepo->getHourlySummary($startDate, $endDate);
    }

    /**
     * Resumen de demanda de viajes por día de la semana (1 = Lunes, 7 = Domingo).
     */
    public function getDailySummary(?string $startDate = null, ?string $endDate = null): array
    {
        return $this->reportRepo->getDailySummary($startDate, $endDate);
    }

    /**
     * Distribución de puntuaciones (estrellas de 1 a 5) y comentarios recientes.
     */
    public function getRatingsDistribution(?string $startDate = null, ?string $endDate = null): array
    {
        $distribution = $this->reportRepo->getRatingsDistribution($startDate, $endDate);
        $comments = $this->reportRepo->getRecentComments($startDate, $endDate);

        return [
            'distribution' => $distribution,
            'comments' => $comments,
        ];
    }

    /**
     * Tiempos promedio de viaje y frecuencia agrupados por rutas comunes.
     */
    public function getRoutesPerformance(?string $startDate = null, ?string $endDate = null): array
    {
        return $this->reportRepo->getRoutesPerformance($startDate, $endDate);
    }

    /**
     * Resumen consolidado de todas las métricas de reportes en una sola consulta con caché.
     */
    public function getAllSummary(?string $startDate = null, ?string $endDate = null): array
    {
        $cacheKey = 'reports_all_summary_' . md5($startDate . '_' . $endDate);
        return Cache::remember($cacheKey, 120, function () use ($startDate, $endDate) {
            $totalCompleted = $this->reportRepo->getCompletedTripsCount($startDate, $endDate);
            $safeTotalCompleted = max($totalCompleted, 1);

            $drivers = $this->reportRepo->getDriversSummary(null, null, $startDate, $endDate);
            $destinations = $this->reportRepo->getDestinationsSummary($safeTotalCompleted, $startDate, $endDate);
            $hourly = $this->reportRepo->getHourlySummary($startDate, $endDate);
            $daily = $this->reportRepo->getDailySummary($startDate, $endDate);
            $distribution = $this->reportRepo->getRatingsDistribution($startDate, $endDate);
            $comments = $this->reportRepo->getRecentComments($startDate, $endDate);
            $routes = $this->reportRepo->getRoutesPerformance($startDate, $endDate);
            $stats = $this->reportRepo->getDashboardStats($startDate, $endDate);

            return [
                'drivers' => $drivers,
                'destinations' => $destinations,
                'hourly' => $hourly,
                'daily' => $daily,
                'ratings' => [
                    'distribution' => $distribution,
                    'comments' => $comments,
                ],
                'routes' => $routes,
                'stats' => $stats,
            ];
        });
    }

    /**
     * Obtener estadísticas generales para el Dashboard del sistema con caché de 15 minutos.
     */
    public function getDashboardStats(?string $startDate = null, ?string $endDate = null): array
    {
        $cacheKey = 'dashboard_stats_' . md5($startDate . '_' . $endDate);
        return Cache::remember($cacheKey, 15, function () use ($startDate, $endDate) {
            return $this->reportRepo->getDashboardStats($startDate, $endDate);
        });
    }

    /**
     * Limpia la caché y emite el evento WebSockets con las métricas actualizadas
     */
    public function broadcastDashboardUpdates()
    {
        $cacheKey = 'dashboard_stats_' . md5('_');
        Cache::forget($cacheKey);
        
        $stats = $this->getDashboardStats();
        $hourly = $this->getHourlySummary();
        
        broadcast(new DashboardStatsUpdated($stats, $hourly));
    }
}
