<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('drivers', function ($user) {
    // Logic to authorize driver.
    // Assuming 'role_id' 3 is Driver, or simply check if user exists for now.
    // In production, use $user->hasRole('driver') or similar.
    return true;
});

Broadcast::channel('passenger.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
