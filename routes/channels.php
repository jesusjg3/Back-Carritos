<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Trip;

Broadcast::channel('drivers', function ($user) {
    // Logic to authorize driver.
    // Assuming 'role_id' 3 is Driver, or simply check if user exists for now.
    // In production, use $user->hasRole('driver') or similar.
    return true;
});

Broadcast::channel('passenger.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('trip.{tripId}', function ($user, $tripId) {
    $trip = Trip::find($tripId);
    // Autorizar solo al pasajero o conductor de ese viaje específico
    return $trip && ($trip->passenger_id === $user->id || $trip->driver_id === $user->id);
});
