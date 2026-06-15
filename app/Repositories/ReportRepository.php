<?php

namespace App\Repositories;

use App\Models\Trip;
use App\Models\User;
use App\Models\TripRating;
use App\Models\State;
use Illuminate\Support\Facades\DB;

class ReportRepository
{
    /**
     * Obtener resumen de estadísticas de viajes
     */
    public function getTripsSummary(): array
    {
        $totalTrips = Trip::count();
        
        $activeTrips = Trip::whereIn('state_id', [
            State::REQUESTED,
            State::ACCEPTED,
            State::STARTED
        ])->count();

        $completedTrips = Trip::where('state_id', State::FINISHED)->count();
        $cancelledTrips = Trip::where('state_id', State::CANCELLED)->count();

        $totalDistance = Trip::where('state_id', State::FINISHED)->sum('distance');
        $avgDistance = Trip::where('state_id', State::FINISHED)->avg('distance') ?? 0;

        // Calcular la duración promedio de forma compatible con SQLite y MySQL
        $avgDuration = 0;
        if ($completedTrips > 0) {
            $driver = DB::connection()->getDriverName();
            if ($driver === 'sqlite') {
                $avgDuration = Trip::where('state_id', State::FINISHED)
                    ->selectRaw("AVG(strftime('%s', updated_at) - strftime('%s', created_at)) as avg_duration")
                    ->value('avg_duration') ?? 0;
            } else {
                $avgDuration = Trip::where('state_id', State::FINISHED)
                    ->selectRaw("AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_duration")
                    ->value('avg_duration') ?? 0;
            }
        }

        return [
            'total_trips' => $totalTrips,
            'active_trips' => $activeTrips,
            'completed_trips' => $completedTrips,
            'cancelled_trips' => $cancelledTrips,
            'total_distance_km' => round($totalDistance, 2),
            'avg_distance_km' => round($avgDistance, 2),
            'avg_duration_seconds' => round($avgDuration, 0),
        ];
    }

    /**
     * Obtener los mejores conductores
     */
    public function getTopDrivers(int $limit): array
    {
        $drivers = User::whereHas('rol', function ($query) {
            $query->where('rol_name', 'conductor');
        })
        ->withCount(['TripsAsDrivers as completed_trips_count' => function ($query) {
            $query->where('state_id', State::FINISHED);
        }])
        ->withSum(['TripsAsDrivers as total_distance_km' => function ($query) {
            $query->where('state_id', State::FINISHED);
        }], 'distance')
        ->orderBy('completed_trips_count', 'desc')
        ->limit($limit)
        ->get();

        return $drivers->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'completed_trips_count' => $user->completed_trips_count ?? 0,
                'rating_average' => (float)($user->score ?? 5.0),
                'rating_count' => (int)($user->rating_count ?? 0),
                'total_distance_km' => round($user->total_distance_km ?? 0, 2),
            ];
        })->toArray();
    }

    /**
     * Obtener los pasajeros con más actividad
     */
    public function getTopPassengers(int $limit): array
    {
        $passengers = User::whereHas('rol', function ($query) {
            $query->where('rol_name', 'pasajero');
        })
        ->withCount(['TripsAsPassengers as total_trips_count'])
        ->orderBy('total_trips_count', 'desc')
        ->limit($limit)
        ->get();

        return $passengers->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'total_trips_count' => $user->total_trips_count ?? 0,
                'rating_average' => (float)($user->score ?? 5.0),
                'rating_count' => (int)($user->rating_count ?? 0),
            ];
        })->toArray();
    }

    /**
     * Obtener viajes por periodo de fecha
     */
    public function getTripsByDate(string $startDate, string $endDate): array
    {
        $trips = Trip::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $trips->toArray();
    }

    /**
     * Obtener distribución de calificaciones
     */
    public function getRatingsDistribution(): array
    {
        // Agrupa por el valor entero redondeado de la calificación
        $distribution = TripRating::selectRaw('ROUND(rating) as stars, COUNT(*) as count')
            ->groupBy('stars')
            ->orderBy('stars', 'desc')
            ->get();

        // Asegurar formato de retorno consistente
        $result = [
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0
        ];

        foreach ($distribution as $row) {
            $stars = (int)$row->stars;
            if ($stars >= 1 && $stars <= 5) {
                $result[$stars] = (int)$row->count;
            }
        }

        return $result;
    }

    /**
     * Obtener coordenadas de los viajes para cobertura
     */
    public function getCoverageMap(int $limit = 500): array
    {
        return Trip::select('id', 'origin_lat', 'origin_lng', 'destination_lat', 'destination_lng', 'distance', 'state_id')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
