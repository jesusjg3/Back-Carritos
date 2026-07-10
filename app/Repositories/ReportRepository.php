<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Trip;
use App\Models\Rol;
use App\Models\Destination;
use App\Models\State;
use Illuminate\Support\Facades\DB;

class ReportRepository
{
    /**
     * Obtener resumen de rendimiento de conductores.
     */
    public function getDriversSummary(?string $search = null, ?int $perPage = null)
    {
        $query = User::whereHas('rol', function ($query) {
                $query->where('rol_name', 'conductor');
            })
            ->leftJoin('trips', 'users.id', '=', 'trips.driver_id')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'users.score',
                'users.rating_count',
                'users.is_active',
                DB::raw('COUNT(CASE WHEN trips.state_id = ' . State::FINISHED . ' THEN 1 END) as completed_trips'),
                DB::raw('COUNT(CASE WHEN trips.state_id = ' . State::CANCELLED . ' THEN 1 END) as canceled_trips'),
                DB::raw('CAST(COALESCE(SUM(CASE WHEN trips.state_id = ' . State::FINISHED . ' THEN trips.passengers_count ELSE 0 END), 0) AS INTEGER) as total_passengers'),
                DB::raw('COALESCE(ROUND(AVG(CASE WHEN trips.state_id = ' . State::FINISHED . ' THEN trips.passengers_count END)::numeric, 1), 0.0) as avg_passengers'),
                DB::raw('COALESCE(ROUND(AVG(CASE WHEN trips.state_id = ' . State::FINISHED . ' THEN EXTRACT(EPOCH FROM (trips.updated_at - trips.created_at))/60 END)::numeric, 1), 0.0) as avg_duration_minutes')
            )
            ->groupBy('users.id', 'users.name', 'users.email', 'users.score', 'users.rating_count', 'users.is_active');

        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('users.name', 'ilike', "%{$search}%")
                    ->orWhere('users.email', 'ilike', "%{$search}%");
            });
        }

        if ($perPage) {
            return $query->paginate($perPage);
        }

        return $query->get()->toArray();
    }

    /**
     * Obtener el conteo total de viajes completados.
     */
    public function getCompletedTripsCount(): int
    {
        return Trip::where('state_id', State::FINISHED)->count();
    }

    /**
     * Obtener resumen de destinos con porcentajes.
     */
    public function getDestinationsSummary(int $safeTotalCompleted): array
    {
        return Trip::where('state_id', State::FINISHED)
            ->select(
                'destination_address',
                DB::raw('COUNT(*) as count'),
                DB::raw('ROUND((COUNT(*)::numeric / ' . $safeTotalCompleted . '::numeric) * 100, 2) as percentage')
            )
            ->groupBy('destination_address')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
    }

    /**
     * Obtener resumen de demanda por horas.
     */
    public function getHourlySummary(): array
    {
        return Trip::where('state_id', State::FINISHED)
            ->select(
                DB::raw('CAST(EXTRACT(HOUR FROM created_at) AS INTEGER) as hour'),
                DB::raw('COUNT(*) as count'),
                DB::raw('CAST(COALESCE(SUM(passengers_count), 0) AS INTEGER) as passengers_count')
            )
            ->groupBy('hour')
            ->orderBy('hour', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Obtener resumen de demanda por día de la semana.
     */
    public function getDailySummary(): array
    {
        return Trip::where('state_id', State::FINISHED)
            ->where('created_at', '>=', now()->subDays(15))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Obtener la distribución de puntuaciones (estrellas).
     */
    public function getRatingsDistribution(): array
    {
        return DB::table('trip_ratings')
            ->select(
                DB::raw('CAST(rating AS INTEGER) as stars'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('stars')
            ->orderBy('stars', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Obtener los comentarios recientes.
     */
    public function getRecentComments(): array
    {
        return DB::table('trip_ratings')
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
            ->get()
            ->toArray();
    }

    /**
     * Obtener el rendimiento por rutas.
     */
    public function getRoutesPerformance(): array
    {
        return Trip::where('state_id', State::FINISHED)
            ->where('created_at', '>=', now()->subDays(15))
            ->select(
                'origin_address',
                'destination_address',
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(ROUND(AVG(EXTRACT(EPOCH FROM (updated_at - created_at))/60)::numeric, 1), 0.0) as avg_duration_minutes')
            )
            ->groupBy('origin_address', 'destination_address')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
    }

    /**
     * Obtener estadísticas generales para el dashboard.
     */
    public function getDashboardStats(): array
    {
        $driverRole = Rol::where('rol_name', 'conductor')->first();
        $driverRoleId = $driverRole ? $driverRole->id : 3;

        $adminRole = Rol::where('rol_name', 'admin')->first();
        $adminRoleId = $adminRole ? $adminRole->id : 1;

        $passengerRole = Rol::where('rol_name', 'pasajero')->first();
        $passengerRoleId = $passengerRole ? $passengerRole->id : 2;

        return [
            'users' => User::withTrashed()->count(),
            'drivers' => User::withTrashed()->where('rol_id', $driverRoleId)->count(),
            'admins' => User::withTrashed()->where('rol_id', $adminRoleId)->count(),
            'passengers' => User::withTrashed()->where('rol_id', $passengerRoleId)->count(),
            'destinations' => Destination::withTrashed()->count(),
            'trips' => Trip::count(),
            'active' => Trip::whereIn('state_id', [State::REQUESTED, State::ACCEPTED, State::STARTED])->count(),
            'completed' => Trip::where('state_id', State::FINISHED)->count(),
        ];
    }
}
