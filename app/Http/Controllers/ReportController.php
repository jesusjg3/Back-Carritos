<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ReportController extends Controller
{
    /**
     * Resumen de rendimiento de todos los conductores.
     */
    public function driversSummary(): JsonResponse
    {
        try {
            $drivers = User::whereHas('rol', function ($q) {
                    $q->where('rol_name', 'conductor');
                })
                ->leftJoin('trips', 'users.id', '=', 'trips.driver_id')
                ->select(
                    'users.id',
                    'users.name',
                    'users.email',
                    'users.score',
                    'users.rating_count',
                    'users.is_active',
                    DB::raw('COUNT(CASE WHEN trips.state_id = 3 THEN 1 END) as completed_trips'),
                    DB::raw('COUNT(CASE WHEN trips.state_id = 5 THEN 1 END) as canceled_trips'),
                    DB::raw('CAST(COALESCE(SUM(CASE WHEN trips.state_id = 3 THEN trips.passengers_count ELSE 0 END), 0) AS INTEGER) as total_passengers'),
                    DB::raw('COALESCE(ROUND(AVG(CASE WHEN trips.state_id = 3 THEN trips.passengers_count END)::numeric, 1), 0.0) as avg_passengers'),
                    DB::raw('COALESCE(ROUND(AVG(CASE WHEN trips.state_id = 3 THEN EXTRACT(EPOCH FROM (trips.updated_at - trips.created_at))/60 END)::numeric, 1), 0.0) as avg_duration_minutes')
                )
                ->groupBy('users.id', 'users.name', 'users.email', 'users.score', 'users.rating_count', 'users.is_active')
                ->get();

            return response()->json($drivers);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Resumen de demanda por destinos (frecuencia de viajes completados).
     */
    public function destinationsSummary(): JsonResponse
    {
        try {
            $totalCompleted = Trip::where('state_id', 3)->count();

            $destinations = Trip::where('state_id', 3)
                ->select(
                    'destination_address',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('ROUND((COUNT(*)::numeric / ' . max($totalCompleted, 1) . '::numeric) * 100, 2) as percentage')
                )
                ->groupBy('destination_address')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get();

            return response()->json($destinations);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Resumen de viajes completados por hora del día (0-23).
     */
    public function hourlySummary(): JsonResponse
    {
        try {
            $hourly = Trip::where('state_id', 3)
                ->select(
                    DB::raw('CAST(EXTRACT(HOUR FROM created_at) AS INTEGER) as hour'),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('CAST(COALESCE(SUM(passengers_count), 0) AS INTEGER) as passengers_count')
                )
                ->groupBy('hour')
                ->orderBy('hour', 'asc')
                ->get();

            return response()->json($hourly);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Resumen de demanda de viajes por día de la semana (1 = Lunes, 7 = Domingo).
     */
    public function dailySummary(): JsonResponse
    {
        try {
            $daily = Trip::where('state_id', 3)
                ->select(
                    DB::raw('CAST(EXTRACT(ISODOW FROM created_at) AS INTEGER) as day_of_week'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('day_of_week')
                ->orderBy('day_of_week', 'asc')
                ->get();

            return response()->json($daily);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Distribución de puntuaciones (estrellas de 1 a 5) y comentarios recientes.
     */
    public function ratingsDistribution(): JsonResponse
    {
        try {
            $distribution = DB::table('trip_ratings')
                ->select(
                    DB::raw('CAST(rating AS INTEGER) as stars'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('stars')
                ->orderBy('stars', 'desc')
                ->get();

            $comments = DB::table('trip_ratings')
                ->join('users', 'trip_ratings.emitter_id', '=', 'users.id')
                ->select(
                    'trip_ratings.rating',
                    'trip_ratings.comment',
                    'trip_ratings.created_at',
                    'users.name as passenger_name'
                )
                ->whereNotNull('trip_ratings.comment')
                ->where('trip_ratings.comment', '!=', '')
                ->orderBy('trip_ratings.created_at', 'desc')
                ->limit(5)
                ->get();

            return response()->json([
                'distribution' => $distribution,
                'comments' => $comments
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Tiempos promedio de viaje y frecuencia agrupados por rutas comunes.
     */
    public function routesPerformance(): JsonResponse
    {
        try {
            $routes = Trip::where('state_id', 3)
                ->select(
                    'origin_address',
                    'destination_address',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('COALESCE(ROUND(AVG(EXTRACT(EPOCH FROM (updated_at - created_at))/60)::numeric, 1), 0.0) as avg_duration_minutes')
                )
                ->groupBy('origin_address', 'destination_address')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get();

            return response()->json($routes);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Resumen consolidado de todas las métricas de reportes en una sola consulta con caché.
     */
    public function allSummary(): JsonResponse
    {
        try {
            $summary = Cache::remember('reports_all_summary', 120, function () {
                $totalCompleted = Trip::where('state_id', 3)->count();

                // 1. Conductores
                $drivers = User::whereHas('rol', function ($q) {
                        $q->where('rol_name', 'conductor');
                    })
                    ->leftJoin('trips', 'users.id', '=', 'trips.driver_id')
                    ->select(
                        'users.id',
                        'users.name',
                        'users.email',
                        'users.score',
                        'users.rating_count',
                        'users.is_active',
                        DB::raw('COUNT(CASE WHEN trips.state_id = 3 THEN 1 END) as completed_trips'),
                        DB::raw('COUNT(CASE WHEN trips.state_id = 5 THEN 1 END) as canceled_trips'),
                        DB::raw('CAST(COALESCE(SUM(CASE WHEN trips.state_id = 3 THEN trips.passengers_count ELSE 0 END), 0) AS INTEGER) as total_passengers'),
                        DB::raw('COALESCE(ROUND(AVG(CASE WHEN trips.state_id = 3 THEN trips.passengers_count END)::numeric, 1), 0.0) as avg_passengers'),
                        DB::raw('COALESCE(ROUND(AVG(CASE WHEN trips.state_id = 3 THEN EXTRACT(EPOCH FROM (trips.updated_at - trips.created_at))/60 END)::numeric, 1), 0.0) as avg_duration_minutes')
                    )
                    ->groupBy('users.id', 'users.name', 'users.email', 'users.score', 'users.rating_count', 'users.is_active')
                    ->get();

                // 2. Destinos
                $destinations = Trip::where('state_id', 3)
                    ->select(
                        'destination_address',
                        DB::raw('COUNT(*) as count'),
                        DB::raw('ROUND((COUNT(*)::numeric / ' . max($totalCompleted, 1) . '::numeric) * 100, 2) as percentage')
                    )
                    ->groupBy('destination_address')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get();

                // 3. Horas
                $hourly = Trip::where('state_id', 3)
                    ->select(
                        DB::raw('CAST(EXTRACT(HOUR FROM created_at) AS INTEGER) as hour'),
                        DB::raw('COUNT(*) as count'),
                        DB::raw('CAST(COALESCE(SUM(passengers_count), 0) AS INTEGER) as passengers_count')
                    )
                    ->groupBy('hour')
                    ->orderBy('hour', 'asc')
                    ->get();

                // 4. Días
                $daily = Trip::where('state_id', 3)
                    ->select(
                        DB::raw('CAST(EXTRACT(ISODOW FROM created_at) AS INTEGER) as day_of_week'),
                        DB::raw('COUNT(*) as count')
                    )
                    ->groupBy('day_of_week')
                    ->orderBy('day_of_week', 'asc')
                    ->get();

                // 5. Calificaciones y comentarios
                $distribution = DB::table('trip_ratings')
                    ->select(
                        DB::raw('CAST(rating AS INTEGER) as stars'),
                        DB::raw('COUNT(*) as count')
                    )
                    ->groupBy('stars')
                    ->orderBy('stars', 'desc')
                    ->get();

                $comments = DB::table('trip_ratings')
                    ->join('users', 'trip_ratings.emitter_id', '=', 'users.id')
                    ->select(
                        'trip_ratings.rating',
                        'trip_ratings.comment',
                        'trip_ratings.created_at',
                        'users.name as passenger_name'
                    )
                    ->whereNotNull('trip_ratings.comment')
                    ->where('trip_ratings.comment', '!=', '')
                    ->orderBy('trip_ratings.created_at', 'desc')
                    ->limit(5)
                    ->get();

                // 6. Rutas
                $routes = Trip::where('state_id', 3)
                    ->select(
                        'origin_address',
                        'destination_address',
                        DB::raw('COUNT(*) as count'),
                        DB::raw('COALESCE(ROUND(AVG(EXTRACT(EPOCH FROM (updated_at - created_at))/60)::numeric, 1), 0.0) as avg_duration_minutes')
                    )
                    ->groupBy('origin_address', 'destination_address')
                    ->orderBy('count', 'desc')
                    ->limit(5)
                    ->get();

                // 7. Stats del Dashboard
                $driverRole = \App\Models\Rol::where('rol_name', 'conductor')->first();
                $driverRoleId = $driverRole ? $driverRole->id : 3;

                $adminRole = \App\Models\Rol::where('rol_name', 'admin')->first();
                $adminRoleId = $adminRole ? $adminRole->id : 1;

                $passengerRole = \App\Models\Rol::where('rol_name', 'pasajero')->first();
                $passengerRoleId = $passengerRole ? $passengerRole->id : 2;

                $stats = [
                    'users' => \App\Models\User::withTrashed()->count(),
                    'drivers' => \App\Models\User::withTrashed()->where('rol_id', $driverRoleId)->count(),
                    'admins' => \App\Models\User::withTrashed()->where('rol_id', $adminRoleId)->count(),
                    'passengers' => \App\Models\User::withTrashed()->where('rol_id', $passengerRoleId)->count(),
                    'destinations' => \App\Models\Destination::withTrashed()->count(),
                    'trips' => \App\Models\Trip::count(),
                    'active' => \App\Models\Trip::whereIn('state_id', [1, 2, 4])->count(),
                    'completed' => \App\Models\Trip::where('state_id', 3)->count(),
                ];

                return [
                    'drivers' => $drivers,
                    'destinations' => $destinations,
                    'hourly' => $hourly,
                    'daily' => $daily,
                    'ratings' => [
                        'distribution' => $distribution,
                        'comments' => $comments
                    ],
                    'routes' => $routes,
                    'stats' => $stats
                ];
            });

            return response()->json($summary);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
