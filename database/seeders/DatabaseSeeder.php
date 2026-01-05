<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            RolSeeder::class,
            StateSeeder::class,
            TabSeeder::class,
            DestinationSeeder::class,
        ]);

        $adminRole = \App\Models\Rol::where('rol_name', 'admin')->first();
        $pasajeroRole = \App\Models\Rol::where('rol_name', 'pasajero')->first();
        $conductorRole = \App\Models\Rol::where('rol_name', 'conductor')->first();

        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('12345678'),
            'rol_id' => $adminRole->id ?? 1,
        ]);

        User::factory()->create([
            'name' => 'Pasajero User',
            'email' => 'pasajero@test.com',
            'password' => bcrypt('12345678'),
            'rol_id' => $pasajeroRole->id ?? 2,
        ]);

        User::factory()->create([
            'name' => 'Conductor User',
            'email' => 'conductor@test.com',
            'password' => bcrypt('12345678'),
            'rol_id' => $conductorRole->id ?? 3,
        ]);
    }
}

