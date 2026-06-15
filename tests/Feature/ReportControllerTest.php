<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\State;
use App\Models\Trip;
use App\Models\TripRating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $passenger;
    private User $driver;
    private Rol $adminRole;
    private Rol $passengerRole;
    private Rol $driverRole;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Crear roles necesarios
        $this->adminRole = Rol::create(['rol_name' => 'admin']);
        $this->passengerRole = Rol::create(['rol_name' => 'pasajero']);
        $this->driverRole = Rol::create(['rol_name' => 'conductor']);

        // 2. Crear estados
        State::create(['id' => State::REQUESTED, 'state_name' => 'SOLICITADO']);
        State::create(['id' => State::ACCEPTED, 'state_name' => 'ACEPTADO']);
        State::create(['id' => State::FINISHED, 'state_name' => 'TERMINADO']);
        State::create(['id' => State::STARTED, 'state_name' => 'INICIADO']);
        State::create(['id' => State::CANCELLED, 'state_name' => 'CANCELADO']);

        // 3. Crear usuarios
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'rol_id' => $this->adminRole->id,
            'is_active' => true,
        ]);

        $this->passenger = User::create([
            'name' => 'Passenger User',
            'email' => 'passenger@test.com',
            'password' => bcrypt('password123'),
            'rol_id' => $this->passengerRole->id,
            'is_active' => true,
        ]);

        $this->driver = User::create([
            'name' => 'Driver User',
            'email' => 'driver@test.com',
            'password' => bcrypt('password123'),
            'rol_id' => $this->driverRole->id,
            'is_active' => true,
            'score' => 4.5,
            'rating_count' => 1
        ]);
    }

    /**
     * Helper to get Authorization Header
     */
    private function getAuthHeader(User $user): array
    {
        $token = auth('api')->login($user);
        return [
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ];
    }

    public function test_unauthenticated_user_cannot_access_reports(): void
    {
        $response = $this->getJson('/api/reports/summary');
        $response->assertStatus(401);
    }

    public function test_non_admin_cannot_access_reports(): void
    {
        $headers = $this->getAuthHeader($this->passenger);

        $response = $this->getJson('/api/reports/summary', $headers);
        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'No tienes permisos de administrador para realizar esta acción.'
        ]);
    }

    public function test_admin_can_access_summary_report(): void
    {
        // Crear un viaje terminado
        $trip = Trip::create([
            'passenger_id' => $this->passenger->id,
            'driver_id' => $this->driver->id,
            'state_id' => State::FINISHED,
            'origin_lat' => -0.9678,
            'origin_lng' => -80.7126,
            'destination_lat' => -0.9650,
            'destination_lng' => -80.7100,
            'distance' => 5.5,
            'passengers_count' => 1,
            'created_at' => now()->subMinutes(10),
            'updated_at' => now(),
        ]);

        $headers = $this->getAuthHeader($this->admin);
        $response = $this->getJson('/api/reports/summary', $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total_trips',
            'active_trips',
            'completed_trips',
            'cancelled_trips',
            'total_distance_km',
            'avg_distance_km',
            'avg_duration_seconds',
        ]);

        $response->assertJson([
            'total_trips' => 1,
            'completed_trips' => 1,
            'total_distance_km' => 5.5,
        ]);
    }

    public function test_admin_can_access_top_drivers_report(): void
    {
        // Crear un viaje terminado para que compute en la distancia y conteo
        Trip::create([
            'passenger_id' => $this->passenger->id,
            'driver_id' => $this->driver->id,
            'state_id' => State::FINISHED,
            'origin_lat' => -0.9678,
            'origin_lng' => -80.7126,
            'destination_lat' => -0.9650,
            'destination_lng' => -80.7100,
            'distance' => 12.3,
            'passengers_count' => 1,
        ]);

        $headers = $this->getAuthHeader($this->admin);
        $response = $this->getJson('/api/reports/top-drivers', $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'email',
                'completed_trips_count',
                'rating_average',
                'rating_count',
                'total_distance_km',
            ]
        ]);

        $response->assertJsonFragment([
            'name' => 'Driver User',
            'completed_trips_count' => 1,
            'total_distance_km' => 12.3
        ]);
    }

    public function test_admin_can_access_top_passengers_report(): void
    {
        // Crear un viaje para el pasajero
        Trip::create([
            'passenger_id' => $this->passenger->id,
            'driver_id' => $this->driver->id,
            'state_id' => State::FINISHED,
            'origin_lat' => -0.9678,
            'origin_lng' => -80.7126,
            'destination_lat' => -0.9650,
            'destination_lng' => -80.7100,
            'distance' => 3.0,
            'passengers_count' => 1,
        ]);

        $headers = $this->getAuthHeader($this->admin);
        $response = $this->getJson('/api/reports/top-passengers', $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'email',
                'total_trips_count',
                'rating_average',
                'rating_count',
            ]
        ]);

        $response->assertJsonFragment([
            'name' => 'Passenger User',
            'total_trips_count' => 1,
        ]);
    }

    public function test_admin_can_access_trips_by_date_report(): void
    {
        Trip::create([
            'passenger_id' => $this->passenger->id,
            'driver_id' => $this->driver->id,
            'state_id' => State::FINISHED,
            'origin_lat' => -0.9678,
            'origin_lng' => -80.7126,
            'destination_lat' => -0.9650,
            'destination_lng' => -80.7100,
            'distance' => 3.0,
            'passengers_count' => 1,
        ]);

        $headers = $this->getAuthHeader($this->admin);
        $response = $this->getJson('/api/reports/trips-by-date', $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'date',
                'count',
            ]
        ]);
    }

    public function test_admin_can_access_ratings_distribution_report(): void
    {
        $trip = Trip::create([
            'passenger_id' => $this->passenger->id,
            'driver_id' => $this->driver->id,
            'state_id' => State::FINISHED,
            'origin_lat' => -0.9678,
            'origin_lng' => -80.7126,
            'destination_lat' => -0.9650,
            'destination_lng' => -80.7100,
            'distance' => 3.0,
            'passengers_count' => 1,
        ]);

        TripRating::create([
            'trip_id' => $trip->id,
            'emitter_id' => $this->passenger->id,
            'receiver_id' => $this->driver->id,
            'rating' => 5,
            'comment' => 'Excelente servicio'
        ]);

        $headers = $this->getAuthHeader($this->admin);
        $response = $this->getJson('/api/reports/ratings-distribution', $headers);

        $response->assertStatus(200);
        $response->assertJson([
            '5' => 1,
            '4' => 0,
        ]);
    }

    public function test_admin_can_access_coverage_map_report(): void
    {
        Trip::create([
            'passenger_id' => $this->passenger->id,
            'driver_id' => $this->driver->id,
            'state_id' => State::FINISHED,
            'origin_lat' => -0.9678,
            'origin_lng' => -80.7126,
            'destination_lat' => -0.9650,
            'destination_lng' => -80.7100,
            'distance' => 3.0,
            'passengers_count' => 1,
        ]);

        $headers = $this->getAuthHeader($this->admin);
        $response = $this->getJson('/api/reports/coverage-map', $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'origin_lat',
                'origin_lng',
                'destination_lat',
                'destination_lng',
                'distance',
                'state_id',
            ]
        ]);
    }
}
