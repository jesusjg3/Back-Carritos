<?php

namespace Tests\Feature;

use App\Http\Requests\StoreTripRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UpdateAdminRequest;
use App\Events\DashboardStatsUpdated;
use App\Events\DriverGlobalLocationUpdated;
use App\Events\DriverOffline;
use App\Events\TripAccepted;
use App\Models\Trip;
use App\Models\State;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    public function test_public_registration_does_not_accept_role_fields(): void
    {
        $rules = (new RegisterRequest())->rules();

        $this->assertArrayNotHasKey('role_id', $rules);
        $this->assertArrayNotHasKey('rol_id', $rules);
        $this->assertSame('required|string|min:8|confirmed', $rules['password']);
    }

    public function test_management_routes_require_their_specific_permissions(): void
    {
        $this->assertStringContainsString(
            'permission:manage_shifts',
            implode('|', Route::getRoutes()->getByName('shifts.index')->middleware())
        );
        $this->assertStringContainsString(
            'permission:manage_events',
            implode('|', Route::getRoutes()->getByName('events.index')->middleware())
        );
        $this->assertStringContainsString(
            'permission:manage_assignments',
            implode('|', Route::getRoutes()->getByName('assignments.index')->middleware())
        );
        $uris = collect(Route::getRoutes())
            ->map(fn ($route) => $route->uri())
            ->all();

        $this->assertNotContains('api/test-broadcast/{id}', $uris);
        $this->assertNotContains('api/test-broadcast-started/{id}', $uris);
    }

    public function test_user_and_admin_management_are_separated(): void
    {
        $userRules = (new UpdateUserRequest())->rules();
        $adminRules = (new UpdateAdminRequest())->rules();

        $this->assertArrayNotHasKey('rol_id', $userRules);
        $this->assertArrayNotHasKey('permissions', $userRules);
        $this->assertArrayHasKey('permissions', $adminRules);

        $adminUpdate = collect(Route::getRoutes())->first(fn ($route) =>
            $route->uri() === 'api/users/admins/{id}' && in_array('PUT', $route->methods(), true)
        );
        $commonUpdate = collect(Route::getRoutes())->first(fn ($route) =>
            $route->uri() === 'api/users/{id}' && in_array('PUT', $route->methods(), true)
        );

        $this->assertContains('permission:manage_admins', $adminUpdate?->middleware() ?? []);
        $this->assertContains('permission:manage_users', $commonUpdate?->middleware() ?? []);
    }

    public function test_trip_request_outside_the_campus_geofence_is_rejected(): void
    {
        config([
            'services.campus.geofence_enabled' => true,
            'services.campus.latitude' => -0.9525,
            'services.campus.longitude' => -80.7450,
            'services.campus.radius_km' => 1.5,
        ]);

        $request = StoreTripRequest::create('/api/trips/request', 'POST', [
            'origin_lat' => -0.9678,
            'origin_lng' => -80.7126,
            'origin_address' => 'Origen de prueba',
            'destination_lat' => -0.9530,
            'destination_lng' => -80.7460,
            'destination_address' => 'Destino de prueba',
            'distance' => 1.5,
            'passengers_count' => 1,
        ]);

        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('origin_lat', $validator->errors()->toArray());
    }

    public function test_trip_request_outside_the_campus_is_allowed_when_geofence_is_disabled(): void
    {
        config([
            'services.campus.geofence_enabled' => false,
            'services.campus.latitude' => -0.9525,
            'services.campus.longitude' => -80.7450,
            'services.campus.radius_km' => 1.5,
        ]);

        $request = StoreTripRequest::create('/api/trips/request', 'POST', [
            'origin_lat' => -0.9678,
            'origin_lng' => -80.7126,
            'origin_address' => 'Domicilio de prueba',
            'destination_lat' => -0.9530,
            'destination_lng' => -80.7460,
            'destination_address' => 'Destino de prueba',
            'distance' => 1.5,
            'passengers_count' => 1,
        ]);

        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertFalse($validator->fails());
    }

    public function test_trip_request_routes_require_authentication_and_active_user(): void
    {
        $route = collect(Route::getRoutes())
            ->first(fn ($route) => $route->uri() === 'api/trips/request');

        $this->assertNotNull($route);
        $middleware = $route ? $route->middleware() : [];

        $this->assertContains('auth:api', $middleware);
        $this->assertContains('is_active', $middleware);
        $this->assertContains('role:pasajero', $middleware);
    }

    public function test_trip_actions_are_restricted_to_their_expected_roles(): void
    {
        $routeMiddleware = function (string $uri, string $method): array {
            $route = collect(Route::getRoutes())
                ->first(fn ($candidate) => $candidate->uri() === $uri
                    && in_array($method, $candidate->methods(), true));

            $this->assertNotNull($route, "No se encontró la ruta {$method} {$uri}");
            return $route?->middleware() ?? [];
        };

        $this->assertContains('role:conductor', $routeMiddleware('api/trips/{id}/start', 'POST'));
        $this->assertContains('role:conductor', $routeMiddleware('api/trips/{id}/position', 'POST'));
        $this->assertContains('role:pasajero,conductor', $routeMiddleware('api/trips/{id}/cancel', 'DELETE'));
        $this->assertContains('role:pasajero', $routeMiddleware('api/drivers/nearby', 'GET'));
        $this->assertContains('role:admin', $routeMiddleware('api/users/drivers', 'GET'));

        $refreshRoute = collect(Route::getRoutes())
            ->first(fn ($candidate) => $candidate->uri() === 'api/refresh'
                && in_array('POST', $candidate->methods(), true));
        $this->assertNotNull($refreshRoute);
        $this->assertContains('is_active', $refreshRoute?->middleware() ?? []);
    }

    public function test_trip_state_ids_match_the_frontend_contract(): void
    {
        $this->assertSame(1, State::REQUESTED);
        $this->assertSame(2, State::ACCEPTED);
        $this->assertSame(3, State::FINISHED);
        $this->assertSame(4, State::STARTED);
        $this->assertSame(5, State::CANCELLED);
    }

    public function test_live_location_and_dashboard_events_use_private_channels(): void
    {
        $locationChannels = (new DriverGlobalLocationUpdated(10, -0.95, -80.74))->broadcastOn();
        $offlineChannels = (new DriverOffline(10))->broadcastOn();
        $statsChannels = [(new DashboardStatsUpdated([], []))->broadcastOn()];

        foreach (array_merge($locationChannels, $offlineChannels, $statsChannels) as $channel) {
            $this->assertInstanceOf(PrivateChannel::class, $channel);
        }
    }

    public function test_trip_accepted_targets_the_passenger_private_channel(): void
    {
        $channels = (new TripAccepted(new Trip(), 123))->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('private-passenger.123', $channels[0]->name);
    }
}
