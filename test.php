<?php

use App\Models\User;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

$totalTrips = Trip::count();
$completedTrips = Trip::where('state_id', 3)->count();
$totalDrivers = User::whereHas('rol', function($q) {
    $q->where('rol_name', 'conductor');
})->count();

echo "Conductores: $totalDrivers\n";
echo "Total Viajes: $totalTrips\n";
echo "Viajes Terminados: $completedTrips\n";

$tripsByState = Trip::select('state_id', DB::raw('count(*) as count'))
    ->groupBy('state_id')
    ->get();

echo "Viajes por Estado:\n";
foreach ($tripsByState as $t) {
    echo "  Estado ID {$t->state_id}: {$t->count}\n";
}
