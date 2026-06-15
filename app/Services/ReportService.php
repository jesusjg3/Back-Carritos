<?php

namespace App\Services;

use App\Repositories\ReportRepository;

class ReportService
{
    protected ReportRepository $reportRepo;

    public function __construct(ReportRepository $reportRepo)
    {
        $this->reportRepo = $reportRepo;
    }

    /**
     * Obtener resumen de estadísticas
     */
    public function getSummary(): array
    {
        return $this->reportRepo->getTripsSummary();
    }

    /**
     * Obtener mejores conductores
     */
    public function getTopDrivers(int $limit = 10): array
    {
        return $this->reportRepo->getTopDrivers($limit);
    }

    /**
     * Obtener pasajeros más activos
     */
    public function getTopPassengers(int $limit = 10): array
    {
        return $this->reportRepo->getTopPassengers($limit);
    }

    /**
     * Obtener viajes por periodo
     */
    public function getTripsByDate(string $startDate, string $endDate): array
    {
        return $this->reportRepo->getTripsByDate($startDate, $endDate);
    }

    /**
     * Obtener distribución de calificaciones
     */
    public function getRatingsDistribution(): array
    {
        return $this->reportRepo->getRatingsDistribution();
    }

    /**
     * Obtener mapa de cobertura
     */
    public function getCoverageMap(int $limit = 500): array
    {
        return $this->reportRepo->getCoverageMap($limit);
    }
}
