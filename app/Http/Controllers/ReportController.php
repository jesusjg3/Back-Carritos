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

    public function driversSummary(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $perPage = $request->has('per_page') ? $request->integer('per_page') : null;
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        
        $summary = $this->reportService->getDriversSummary($search, $perPage, $startDate, $endDate);
        return response()->json($summary);
    }

    public function passengersSummary(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $perPage = $request->has('per_page') ? $request->integer('per_page') : null;
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        
        $summary = $this->reportService->getPassengersSummary($search, $perPage, $startDate, $endDate);
        return response()->json($summary);
    }

    public function routesDetailsSummary(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $perPage = $request->has('per_page') ? $request->integer('per_page') : null;
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        
        $summary = $this->reportService->getRoutesDetailsSummary($search, $perPage, $startDate, $endDate);
        return response()->json($summary);
    }

    /**
     * Resumen de demanda por destinos (frecuencia de viajes completados).
     */
    public function destinationsSummary(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $summary = $this->reportService->getDestinationsSummary($startDate, $endDate);
        return response()->json($summary);
    }

    /**
     * Resumen de viajes completados por hora del día (0-23).
     */
    public function hourlySummary(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $summary = $this->reportService->getHourlySummary($startDate, $endDate);
        return response()->json($summary);
    }

    /**
     * Resumen de demanda de viajes por día de la semana (1 = Lunes, 7 = Domingo).
     */
    public function dailySummary(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $summary = $this->reportService->getDailySummary($startDate, $endDate);
        return response()->json($summary);
    }

    /**
     * Distribución de puntuaciones (estrellas de 1 a 5) y comentarios recientes.
     */
    public function ratingsDistribution(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $summary = $this->reportService->getRatingsDistribution($startDate, $endDate);
        return response()->json($summary);
    }

    /**
     * Tiempos promedio de viaje y frecuencia agrupados por rutas comunes.
     */
    public function routesPerformance(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $summary = $this->reportService->getRoutesPerformance($startDate, $endDate);
        return response()->json($summary);
    }

    /**
     * Resumen consolidado de todas las métricas de reportes en una sola consulta con caché.
     */
    public function allSummary(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $summary = $this->reportService->getAllSummary($startDate, $endDate);
        return response()->json($summary);
    }

    /**
     * Estadísticas generales del sistema para el Dashboard.
     */
    public function dashboardStats(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $summary = $this->reportService->getDashboardStats($startDate, $endDate);
        return response()->json($summary);
    }

    public function tripsCoordinates(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $coordinates = $this->reportService->getTripsCoordinates($startDate, $endDate);
        return response()->json($coordinates);
    }

    private function streamCsv(string $filename, array $columns, array $data)
    {
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($data, $columns) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, $columns);
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportDriversReport(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        
        $summary = $this->reportService->getDriversSummary(null, null, $startDate, $endDate);
        
        $columns = ['ID', 'Nombre', 'Correo', 'Calificación', 'Viajes Completados', 'Viajes Cancelados', 'Total Pasajeros', 'Promedio Pasajeros', 'Duración Promedio (min)', 'Estado'];
        $data = [];
        
        $items = is_array($summary) ? $summary : (isset($summary['data']) ? $summary['data'] : []);

        foreach ($items as $item) {
            $data[] = [
                $item['id'] ?? '',
                $item['name'] ?? '',
                $item['email'] ?? '',
                ($item['score'] ?? '0') . ' (' . ($item['rating_count'] ?? '0') . ')',
                $item['completed_trips'] ?? 0,
                $item['canceled_trips'] ?? 0,
                $item['total_passengers'] ?? 0,
                $item['avg_passengers'] ?? 0,
                $item['avg_duration_minutes'] ?? 0,
                (isset($item['is_active']) && $item['is_active']) ? 'Activo' : 'Inactivo',
            ];
        }
        
        return $this->streamCsv('reporte_conductores.csv', $columns, $data);
    }

    public function exportPassengersReport(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        
        $summary = $this->reportService->getPassengersSummary(null, null, $startDate, $endDate);
        
        $columns = ['ID', 'Nombre', 'Correo', 'Calificación', 'Viajes Tomados', 'Viajes Cancelados', 'Estado'];
        $data = [];
        
        $items = is_array($summary) ? $summary : (isset($summary['data']) ? $summary['data'] : []);

        foreach ($items as $item) {
            $data[] = [
                $item['id'] ?? '',
                $item['name'] ?? '',
                $item['email'] ?? '',
                ($item['score'] ?? '0') . ' (' . ($item['rating_count'] ?? '0') . ')',
                $item['completed_trips'] ?? 0,
                $item['canceled_trips'] ?? 0,
                (isset($item['is_active']) && $item['is_active']) ? 'Activo' : 'Inactivo',
            ];
        }
        
        return $this->streamCsv('reporte_pasajeros.csv', $columns, $data);
    }

    public function exportRoutesReport(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        
        $summary = $this->reportService->getRoutesDetailsSummary(null, null, $startDate, $endDate);
        
        $columns = ['Origen', 'Destino', 'Viajes Completados', 'Viajes Cancelados', 'Duración Promedio (min)'];
        $data = [];
        
        $items = is_array($summary) ? $summary : (isset($summary['data']) ? $summary['data'] : []);

        foreach ($items as $item) {
            $data[] = [
                $item['origin_address'] ?? 'Desconocido',
                $item['destination_address'] ?? 'Desconocido',
                $item['completed_trips'] ?? 0,
                $item['canceled_trips'] ?? 0,
                $item['avg_duration_minutes'] ?? 0,
            ];
        }
        
        return $this->streamCsv('reporte_rutas.csv', $columns, $data);
    }
}
