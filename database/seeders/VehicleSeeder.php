<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Vehicle::updateOrCreate(
            ['plate' => 'UTP-001'],
            [
                'brand' => 'Yamaha',
                'model' => 'Golf Cart',
                'color' => 'Blanco',
                'capacity' => 4,
                'status' => 'active',
            ]
        );

        Vehicle::updateOrCreate(
            ['plate' => 'UTP-002'],
            [
                'brand' => 'Club Car',
                'model' => 'Precedent',
                'color' => 'Verde',
                'capacity' => 4,
                'status' => 'active',
            ]
        );
    }
}
