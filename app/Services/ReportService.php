<?php

namespace App\Services;

use App\Repositories\ReportRepository;
use Illuminate\Support\Facades\Cache;

class ReportService
{
    protected ReportRepository $reportRepo;

    public function __construct(ReportRepository $reportRepo)
    {
        $this->reportRepo = $reportRepo;
    }

    public function getDriversSummary(?string $search = null, ?int $perPage = null)
    {
        return $this->reportRepo->getDriversSummary($search, $perPage);
    }

    /**
     * Resumen de demanda por destinos (frecuencia de viajes completados).
     */
    public function getDestinationsSummary(): array
    {
        $totalCompleted = $this->reportRepo->getCompletedTripsCount();
        $safeTotalCompleted = max($totalCompleted, 1);

        return $this->reportRepo->getDestinationsSummary($safeTotalCompleted);
    }

    /**
     * Resumen de viajes completados por hora del día (0-23).
     */
    public function getHourlySummary(): array
    {
        return $this->reportRepo->getHourlySummary();
    }

    /**
     * Resumen de demanda de viajes por día de la semana (1 = Lunes, 7 = Domingo).
     */
    public function getDailySummary(): array
    {
        return $this->reportRepo->getDailySummary();
    }

    /**
     * Distribución de puntuaciones (estrellas de 1 a 5) y comentarios recientes.
     */
    public function getRatingsDistribution(): array
    {
        $distribution = $this->reportRepo->getRatingsDistribution();
        $comments = $this->reportRepo->getRecentComments();

        return [
            'distribution' => $distribution,
            'comments' => $comments,
        ];
    }

    /**
     * Tiempos promedio de viaje y frecuencia agrupados por rutas comunes.
     */
    public function getRoutesPerformance(): array
    {
        return $this->reportRepo->getRoutesPerformance();
    }

    /**
     * Resumen consolidado de todas las métricas de reportes en una sola consulta con caché.
     */
    public function getAllSummary(): array
    {
        return Cache::remember('reports_all_summary', 120, function () {
            $totalCompleted = $this->reportRepo->getCompletedTripsCount();
            $safeTotalCompleted = max($totalCompleted, 1);

            $drivers = $this->reportRepo->getDriversSummary();
            $destinations = $this->reportRepo->getDestinationsSummary($safeTotalCompleted);
            $hourly = $this->reportRepo->getHourlySummary();
            $daily = $this->reportRepo->getDailySummary();
            $distribution = $this->reportRepo->getRatingsDistribution();
            $comments = $this->reportRepo->getRecentComments();
            $routes = $this->reportRepo->getRoutesPerformance();
            $stats = $this->reportRepo->getDashboardStats();

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
    public function getDashboardStats(): array
    {
        return Cache::remember('dashboard_stats', 15, function () {
            return $this->reportRepo->getDashboardStats();
        });
    }
}
