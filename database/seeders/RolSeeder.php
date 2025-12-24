<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define standard roles
        Rol::firstOrCreate(['rol_name' => 'admin']);
        Rol::firstOrCreate(['rol_name' => 'pasajero']);
        Rol::firstOrCreate(['rol_name' => 'conductor']);
    }
}
