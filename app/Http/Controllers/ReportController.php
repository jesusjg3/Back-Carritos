<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function driversSummary(Request $request): JsonResponse
    {
        try {
            $search = $request->query('search');
            $perPage = $request->has('per_page') ? $request->integer('per_page') : null;
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            
            $summary = $this->reportService->getDriversSummary($search, $perPage, $startDate, $endDate);
            return response()->json($summary);
        } catch (Exception $exception) {
            Log::error('Error in driversSummary: ' . $exception->getMessage());
            return response()->json(['error' => 'Ocurrió un error interno al generar el reporte de conductores.'], 500);
        }
    }

    public function passengersSummary(Request $request): JsonResponse
    {
        try {
            $search = $request->query('search');
            $perPage = $request->has('per_page') ? $request->integer('per_page') : null;
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            
            $summary = $this->reportService->getPassengersSummary($search, $perPage, $startDate, $endDate);
            return response()->json($summary);
        } catch (Exception $exception) {
            Log::error('Error in passengersSummary: ' . $exception->getMessage());
            return response()->json(['error' => 'Ocurrió un error interno al generar el reporte de pasajeros.'], 500);
        }
    }

    public function routesDetailsSummary(Request $request): JsonResponse
    {
        try {
            $search = $request->query('search');
            $perPage = $request->has('per_page') ? $request->integer('per_page') : null;
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            
            $summary = $this->reportService->getRoutesDetailsSummary($search, $perPage, $startDate, $endDate);
            return response()->json($summary);
        } catch (Exception $exception) {
            Log::error('Error in routesDetailsSummary: ' . $exception->getMessage());
            return response()->json(['error' => 'Ocurrió un error interno al generar el detalle de rutas.'], 500);
        }
    }

    /**
     * Resumen de demanda por destinos (frecuencia de viajes completados).
     */
    public function destinationsSummary(Request $request): JsonResponse
    {
        try {
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            $summary = $this->reportService->getDestinationsSummary($startDate, $endDate);
            return response()->json($summary);
        } catch (Exception $exception) {
            Log::error('Error in destinationsSummary: ' . $exception->getMessage());
            return response()->json(['error' => 'Ocurrió un error interno al generar el reporte de destinos.'], 500);
        }
    }

    /**
     * Resumen de viajes completados por hora del día (0-23).
     */
    public function hourlySummary(Request $request): JsonResponse
    {
        try {
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            $summary = $this->reportService->getHourlySummary($startDate, $endDate);
            return response()->json($summary);
        } catch (Exception $exception) {
            Log::error('Error in hourlySummary: ' . $exception->getMessage());
            return response()->json(['error' => 'Ocurrió un error interno al generar el reporte por horas.'], 500);
        }
    }

    /**
     * Resumen de demanda de viajes por día de la semana (1 = Lunes, 7 = Domingo).
     */
    public function dailySummary(Request $request): JsonResponse
    {
        try {
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            $summary = $this->reportService->getDailySummary($startDate, $endDate);
            return response()->json($summary);
        } catch (Exception $exception) {
            Log::error('Error in dailySummary: ' . $exception->getMessage());
            return response()->json(['error' => 'Ocurrió un error interno al generar el reporte diario.'], 500);
        }
    }

    /**
     * Distribución de puntuaciones (estrellas de 1 a 5) y comentarios recientes.
     */
    public function ratingsDistribution(Request $request): JsonResponse
    {
        try {
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            $summary = $this->reportService->getRatingsDistribution($startDate, $endDate);
            return response()->json($summary);
        } catch (Exception $exception) {
            Log::error('Error in ratingsDistribution: ' . $exception->getMessage());
            return response()->json(['error' => 'Ocurrió un error interno al generar el reporte de calificaciones.'], 500);
        }
    }

    /**
     * Tiempos promedio de viaje y frecuencia agrupados por rutas comunes.
     */
    public function routesPerformance(Request $request): JsonResponse
    {
        try {
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            $summary = $this->reportService->getRoutesPerformance($startDate, $endDate);
            return response()->json($summary);
        } catch (Exception $exception) {
            Log::error('Error in routesPerformance: ' . $exception->getMessage());
            return response()->json(['error' => 'Ocurrió un error interno al generar el reporte de rutas.'], 500);
        }
    }

    /**
     * Resumen consolidado de todas las métricas de reportes en una sola consulta con caché.
     */
    public function allSummary(Request $request): JsonResponse
    {
        try {
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            $summary = $this->reportService->getAllSummary($startDate, $endDate);
            return response()->json($summary);
        } catch (Exception $exception) {
            Log::error('Error in allSummary: ' . $exception->getMessage());
            return response()->json(['error' => 'Ocurrió un error interno al recopilar todos los reportes.'], 500);
        }
    }

    /**
     * Estadísticas generales del sistema para el Dashboard.
     */
    public function dashboardStats(Request $request): JsonResponse
    {
        try {
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            $summary = $this->reportService->getDashboardStats($startDate, $endDate);
            return response()->json($summary);
        } catch (Exception $exception) {
            Log::error('Error in dashboardStats: ' . $exception->getMessage());
            return response()->json(['error' => 'Ocurrió un error interno al obtener estadísticas del dashboard.'], 500);
        }
    }

    public function tripsCoordinates(Request $request): JsonResponse
    {
        try {
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            $coordinates = $this->reportService->getTripsCoordinates($startDate, $endDate);
            return response()->json($coordinates);
        } catch (Exception $exception) {
            Log::error('Error in tripsCoordinates: ' . $exception->getMessage());
            return response()->json(['error' => 'Ocurrió un error interno al obtener las coordenadas.'], 500);
        }
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
            // Agregamos BOM para que Excel reconozca correctamente UTF-8
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
        try {
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
        } catch (Exception $exception) {
            Log::error('Error in exportDriversReport: ' . $exception->getMessage());
            return response()->json(['error' => 'Ocurrió un error interno al exportar el reporte.'], 500);
        }
    }

    public function exportPassengersReport(Request $request)
    {
        try {
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
        } catch (Exception $exception) {
            Log::error('Error in exportPassengersReport: ' . $exception->getMessage());
            return response()->json(['error' => 'Ocurrió un error interno al exportar el reporte.'], 500);
        }
    }

    public function exportRoutesReport(Request $request)
    {
        try {
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
        } catch (Exception $exception) {
            Log::error('Error in exportRoutesReport: ' . $exception->getMessage());
            return response()->json(['error' => 'Ocurrió un error interno al exportar el reporte.'], 500);
        }
    }
}
