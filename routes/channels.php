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
    if (!$trip) return false;
    
    $isPassenger = $trip->passengers()->where('users.id', $user->id)->exists();
    return $isPassenger || $trip->driver_id === $user->id;
});

// Admin Radar en Vivo
Broadcast::channel('admin.live_tracking', function ($user) {
    return $user->rol_id === 1; // Solo administradores pueden ver el radar global
});

Broadcast::channel('admin.notifications', function ($user) {
    return $user->rol_id === 1; // Solo administradores reciben notificaciones globales
});
