<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$request = Illuminate\Http\Request::create('/api/trips/request', 'POST', [
    'origin_lat' => -0.9525,
    'origin_lng' => -80.7450,
    'origin_address' => 'Mi Ubicación Actual',
    'destination_lat' => -0.9530,
    'destination_lng' => -80.7460,
    'destination_address' => 'Destino Prueba',
    'distance' => 1.5,
    'passengers_count' => 1
]);

$user = App\Models\User::where('email', 'pasajero@example.com')->first();
if($user) {
    $request->setUserResolver(function () use ($user) { return $user; });
} else {
    echo "User not found\n";
    exit;
}

$response = app()->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Content: " . $response->getContent() . "\n";
