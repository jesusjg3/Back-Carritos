<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Trip;
use App\Models\Rol;
use App\Models\Destination;
use App\Models\State;
use App\Models\Shift;
use App\Models\Complaint;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
class ReportRepository
{
    /**
     * Obtener resumen de rendimiento de conductores.
     */
    public function getDriversSummary(?string $search = null, ?int $perPage = null, ?string $startDate = null, ?string $endDate = null)
    {
        $query = User::whereHas('rol', function ($query) {
                $query->where('rol_name', 'conductor');
            })
            ->leftJoin('trips', 'users.id', '=', 'trips.driver_id')
            ->leftJoin('user_ratings', 'users.id', '=', 'user_ratings.user_id')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'user_ratings.score',
                'user_ratings.rating_count',
                'users.is_active',
                DB::raw('COUNT(CASE WHEN trips.state_id = ' . State::FINISHED . ' THEN 1 END) as completed_trips'),
                DB::raw('COUNT(CASE WHEN trips.state_id = ' . State::CANCELLED . ' THEN 1 END) as canceled_trips'),
                DB::raw('CAST(COALESCE(SUM(CASE WHEN trips.state_id = ' . State::FINISHED . ' THEN trips.passengers_count ELSE 0 END), 0) AS INTEGER) as total_passengers'),
                DB::raw('COALESCE(ROUND(AVG(CASE WHEN trips.state_id = ' . State::FINISHED . ' THEN trips.passengers_count END)::numeric, 1), 0.0) as avg_passengers'),
                DB::raw('COALESCE(ROUND(AVG(CASE WHEN trips.state_id = ' . State::FINISHED . ' THEN EXTRACT(EPOCH FROM (trips.updated_at - trips.created_at))/60 END)::numeric, 1), 0.0) as avg_duration_minutes')
            )
            ->groupBy('users.id', 'users.name', 'users.email', 'user_ratings.score', 'user_ratings.rating_count', 'users.is_active');

        $this->applyDateFilters($query, $startDate, $endDate, 'trips.created_at');

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
    public function getCompletedTripsCount(?string $startDate = null, ?string $endDate = null): int
    {
        $query = Trip::where('state_id', State::FINISHED);
        $this->applyDateFilters($query, $startDate, $endDate);
        return $query->count();
    }

    /**
     * Obtener resumen de destinos con porcentajes.
     */
    public function getDestinationsSummary(int $safeTotalCompleted, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = Trip::where('state_id', State::FINISHED)
                     ->whereNotIn('destination_address', ['Mi Ubicación Actual', 'Ubicación personalizada']);
        $this->applyDateFilters($query, $startDate, $endDate);

        return $query->select(
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
    public function getHourlySummary(?string $startDate = null, ?string $endDate = null): array
    {
        $query = Trip::where('state_id', State::FINISHED);
        $this->applyDateFilters($query, $startDate, $endDate);

        return $query->select(
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
    public function getDailySummary(?string $startDate = null, ?string $endDate = null): array
    {
        $query = Trip::where('state_id', State::FINISHED);

        $this->applyDateFilters($query, $startDate, $endDate, 'created_at', now()->subDays(15));

        return $query->select(
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
    public function getRatingsDistribution(?string $startDate = null, ?string $endDate = null): array
    {
        $query = DB::table('trip_ratings');
        $this->applyDateFilters($query, $startDate, $endDate);

        return $query->select(
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
    public function getRecentComments(?string $startDate = null, ?string $endDate = null): array
    {
        $query = DB::table('trip_ratings')
            ->join('users', 'trip_ratings.emitter_id', '=', 'users.id');

        $this->applyDateFilters($query, $startDate, $endDate, 'trip_ratings.created_at');

        return $query->select(
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
    public function getRoutesPerformance(?string $startDate = null, ?string $endDate = null): array
    {
        $query = Trip::where('state_id', State::FINISHED)
                     ->whereNotIn('origin_address', ['Mi Ubicación Actual', 'Ubicación personalizada'])
                     ->whereNotIn('destination_address', ['Mi Ubicación Actual', 'Ubicación personalizada']);

        $this->applyDateFilters($query, $startDate, $endDate, 'created_at', now()->subDays(15));

        return $query->select(
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

    public function getRoutesDetailsSummary(?string $search = null, ?int $perPage = null, ?string $startDate = null, ?string $endDate = null)
    {
        $query = Trip::select(
                'origin_address',
                'destination_address',
                DB::raw('COUNT(CASE WHEN state_id = ' . State::FINISHED . ' THEN 1 END) as completed_trips'),
                DB::raw('COUNT(CASE WHEN state_id = ' . State::CANCELLED . ' THEN 1 END) as canceled_trips'),
                DB::raw('COALESCE(ROUND(AVG(CASE WHEN state_id = ' . State::FINISHED . ' THEN EXTRACT(EPOCH FROM (updated_at - created_at))/60 END)::numeric, 1), 0.0) as avg_duration_minutes')
            )
            ->whereNotIn('origin_address', ['Mi Ubicación Actual', 'Ubicación personalizada'])
            ->whereNotIn('destination_address', ['Mi Ubicación Actual', 'Ubicación personalizada'])
            ->groupBy('origin_address', 'destination_address');

        $this->applyDateFilters($query, $startDate, $endDate);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('origin_address', 'ilike', "%{$search}%")
                  ->orWhere('destination_address', 'ilike', "%{$search}%");
            });
        }

        if ($perPage) {
            return $query->paginate($perPage);
        }
        return $query->get()->toArray();
    }

    public function getPassengersSummary(?string $search = null, ?int $perPage = null, ?string $startDate = null, ?string $endDate = null)
    {
        $query = User::whereHas('rol', function ($q) {
                $q->where('rol_name', 'pasajero');
            })
            ->leftJoin('trip_passengers', 'users.id', '=', 'trip_passengers.passenger_id')
            ->leftJoin('trips', 'trip_passengers.trip_id', '=', 'trips.id')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'users.is_active',
                DB::raw('COUNT(CASE WHEN trips.state_id = ' . State::FINISHED . ' THEN 1 END) as completed_trips'),
                DB::raw('COUNT(CASE WHEN trips.state_id = ' . State::CANCELLED . ' THEN 1 END) as canceled_trips')
            )
            ->groupBy('users.id', 'users.name', 'users.email', 'users.is_active');

        $this->applyDateFilters($query, $startDate, $endDate, 'trips.created_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'ilike', "%{$search}%")
                  ->orWhere('users.email', 'ilike', "%{$search}%");
            });
        }

        if ($perPage) {
            return $query->paginate($perPage);
        }
        return $query->get()->toArray();
    }

    public function getCancellationsOverTime(?string $startDate = null, ?string $endDate = null): array
    {
        $query = Trip::where('state_id', State::CANCELLED);
        $this->applyDateFilters($query, $startDate, $endDate);

        return $query->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->toArray();
    }

    public function getWaitTimeOverTime(?string $startDate = null, ?string $endDate = null): array
    {
        $query = Trip::whereIn('state_id', [State::ACCEPTED, State::STARTED, State::FINISHED]);
        $this->applyDateFilters($query, $startDate, $endDate);

        return $query->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COALESCE(ROUND(AVG(EXTRACT(EPOCH FROM (COALESCE(accepted_at, updated_at) - created_at))/60)::numeric, 1), 0.0) as avg_wait_minutes')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->toArray();
    }

    public function getTripsCoordinates(?string $startDate = null, ?string $endDate = null): array
    {
        $query = Trip::whereNotNull('origin_lat')->whereNotNull('origin_lng');

        $this->applyDateFilters($query, $startDate, $endDate);

        return $query->select('origin_lat as lat', 'origin_lng as lng')
            ->get()
            ->toArray();
    }

    /**
     * Obtener estadísticas generales para el dashboard.
     */
    public function getDashboardStats(?string $startDate = null, ?string $endDate = null): array
    {
        $driverRole = Rol::where('rol_name', 'conductor')->first();
            $driverRoleId = $driverRole ? $driverRole->id : 3;

            $adminRole = Rol::where('rol_name', 'admin')->first();
            $adminRoleId = $adminRole ? $adminRole->id : 1;

            $passengerRole = Rol::where('rol_name', 'pasajero')->first();
            $passengerRoleId = $passengerRole ? $passengerRole->id : 2;

            $tripsQuery = Trip::query();
            $completedTripsQuery = Trip::where('state_id', State::FINISHED);
            $activeTripsQuery = Trip::whereIn('state_id', [State::REQUESTED, State::ACCEPTED, State::STARTED]);

            $this->applyDateFilters($tripsQuery, $startDate, $endDate);
            $this->applyDateFilters($completedTripsQuery, $startDate, $endDate);
            $this->applyDateFilters($activeTripsQuery, $startDate, $endDate);

            return [
                'users' => User::withTrashed()->count(),
                'drivers' => User::withTrashed()->where('rol_id', $driverRoleId)->count(),
                'admins' => User::withTrashed()->where('rol_id', $adminRoleId)->count(),
                'passengers' => User::withTrashed()->where('rol_id', $passengerRoleId)->count(),
                'destinations' => Destination::withTrashed()->count(),
                'vehicles' => Vehicle::count(),
                'trips' => $tripsQuery->count(),
                'active' => $activeTripsQuery->count(),
                'completed' => $completedTripsQuery->count(),
                'approved_disconnects' => Cache::get('driver.disconnect.approved_ids', [])
            ];
    }

    private function applyDateFilters($query, ?string $startDate, ?string $endDate, string $column = 'created_at', $defaultStartDate = null)
    {
        if ($startDate) {
            $query->where($column, '>=', $startDate);
        } elseif ($defaultStartDate) {
            $query->where($column, '>=', $defaultStartDate);
        }

        if ($endDate) {
            $query->where($column, '<=', \Carbon\Carbon::parse($endDate)->endOfDay());
        }

        return $query;
    }
}
