<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Trip;

Broadcast::channel('drivers', function ($user) {
    return $user->is_active && $user->rol?->rol_name === 'conductor';
});

Broadcast::channel('drivers.live', function ($user) {
    return $user->is_active && in_array($user->rol?->rol_name, ['pasajero', 'conductor', 'admin'], true);
});

Broadcast::channel('passenger.{id}', function ($user, $id) {
    return $user->is_active && (int) $user->id === (int) $id;
});

Broadcast::channel('driver.{id}', function ($user, $id) {
    return $user->is_active && (int) $user->id === (int) $id;
});

Broadcast::channel('trip.{tripId}', function ($user, $tripId) {
    $trip = Trip::find($tripId);
    if (!$trip) return false;
    
    $isPassenger = $trip->passengers()->where('users.id', $user->id)->exists();
    return $user->is_active && ($isPassenger || $trip->driver_id === $user->id);
});

// Admin Radar en Vivo
Broadcast::channel('admin.live_tracking', function ($user) {
    return $user->is_active && $user->rol?->rol_name === 'admin' && $user->hasPermission('view_dashboard');
});

Broadcast::channel('dashboard.stats', function ($user) {
    return $user->is_active && $user->rol?->rol_name === 'admin' && $user->hasPermission('view_dashboard');
});

Broadcast::channel('admin.notifications', function ($user) {
    return $user->is_active && $user->rol?->rol_name === 'admin' && $user->hasPermission('manage_disconnects');
});
