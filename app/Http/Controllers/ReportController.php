<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Resumen de rendimiento de todos los conductores.
     */
    public function driversSummary(Request $request): JsonResponse
    {
        try {
            $search = $request->query('search');
            $perPage = $request->has('per_page') ? $request->integer('per_page') : null;
            $summary = $this->reportService->getDriversSummary($search, $perPage);
            return response()->json($summary);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 400);
        }
    }

    /**
     * Resumen de demanda por destinos (frecuencia de viajes completados).
     */
    public function destinationsSummary(): JsonResponse
    {
        try {
            $summary = $this->reportService->getDestinationsSummary();
            return response()->json($summary);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 400);
        }
    }

    /**
     * Resumen de viajes completados por hora del día (0-23).
     */
    public function hourlySummary(): JsonResponse
    {
        try {
            $summary = $this->reportService->getHourlySummary();
            return response()->json($summary);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 400);
        }
    }

    /**
     * Resumen de demanda de viajes por día de la semana (1 = Lunes, 7 = Domingo).
     */
    public function dailySummary(): JsonResponse
    {
        try {
            $summary = $this->reportService->getDailySummary();
            return response()->json($summary);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 400);
        }
    }

    /**
     * Distribución de puntuaciones (estrellas de 1 a 5) y comentarios recientes.
     */
    public function ratingsDistribution(): JsonResponse
    {
        try {
            $summary = $this->reportService->getRatingsDistribution();
            return response()->json($summary);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 400);
        }
    }

    /**
     * Tiempos promedio de viaje y frecuencia agrupados por rutas comunes.
     */
    public function routesPerformance(): JsonResponse
    {
        try {
            $summary = $this->reportService->getRoutesPerformance();
            return response()->json($summary);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 400);
        }
    }

    /**
     * Resumen consolidado de todas las métricas de reportes en una sola consulta con caché.
     */
    public function allSummary(): JsonResponse
    {
        try {
            $summary = $this->reportService->getAllSummary();
            return response()->json($summary);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 400);
        }
    }

    /**
     * Estadísticas generales del sistema para el Dashboard.
     */
    public function dashboardStats(): JsonResponse
    {
        try {
            $summary = $this->reportService->getDashboardStats();
            return response()->json($summary);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 400);
        }
    }
}
