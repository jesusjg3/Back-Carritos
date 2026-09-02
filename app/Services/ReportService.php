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

    private function getCacheKey(string $prefix, ?string $startDate = null, ?string $endDate = null): string
    {
        return $prefix . '_' . md5(($startDate ?? '') . '_' . ($endDate ?? ''));
    }

    public function getDriversSummary(?string $search = null, ?int $perPage = null, ?string $startDate = null, ?string $endDate = null)
    {
        return $this->reportRepo->getDriversSummary($search, $perPage, $startDate, $endDate);
    }

    public function getPassengersSummary(?string $search = null, ?int $perPage = null, ?string $startDate = null, ?string $endDate = null)
    {
        return $this->reportRepo->getPassengersSummary($search, $perPage, $startDate, $endDate);
    }

    public function getRoutesDetailsSummary(?string $search = null, ?int $perPage = null, ?string $startDate = null, ?string $endDate = null)
    {
        return $this->reportRepo->getRoutesDetailsSummary($search, $perPage, $startDate, $endDate);
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
        $cacheKey = $this->getCacheKey('reports_all_summary', $startDate, $endDate);
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
            $cancellations = $this->reportRepo->getCancellationsOverTime($startDate, $endDate);
            $wait_times = $this->reportRepo->getWaitTimeOverTime($startDate, $endDate);
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
                'cancellations' => $cancellations,
                'wait_times' => $wait_times,
                'stats' => $stats,
            ];
        });
    }

    public function getTripsCoordinates(?string $startDate = null, ?string $endDate = null): array
    {
        $cacheKey = $this->getCacheKey('trips_coordinates', $startDate, $endDate);
        return Cache::remember($cacheKey, 300, function () use ($startDate, $endDate) {
            return $this->reportRepo->getTripsCoordinates($startDate, $endDate);
        });
    }

    /**
     * Obtener estadísticas generales para el Dashboard del sistema con caché de 15 minutos.
     */
    public function getDashboardStats(?string $startDate = null, ?string $endDate = null): array
    {
        // v2 invalida el caché generado antes de incluir métricas de quejas.
        $cacheKey = $this->getCacheKey('dashboard_stats_v2', $startDate, $endDate);
        return Cache::remember($cacheKey, 15, function () use ($startDate, $endDate) {
            $stats = $this->reportRepo->getDashboardStats($startDate, $endDate);
            $cancellations = $this->reportRepo->getCancellationsOverTime($startDate, $endDate);
            $wait_times = $this->reportRepo->getWaitTimeOverTime($startDate, $endDate);
            
            $stats['cancellations'] = $cancellations;
            $stats['wait_times'] = $wait_times;
            return $stats;
        });
    }

    /**
     * Limpia la caché y emite el evento WebSockets con las métricas actualizadas
     */
    public function broadcastDashboardUpdates()
    {
        $cacheKey = $this->getCacheKey('dashboard_stats_v2', null, null);
        Cache::forget($cacheKey);
        
        $stats = $this->getDashboardStats();
        $hourly = $this->getHourlySummary();
        
        broadcast(new DashboardStatsUpdated($stats, $hourly));
    }
}
