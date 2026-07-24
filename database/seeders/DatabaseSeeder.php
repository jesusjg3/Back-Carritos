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
        $this->call([
            RolSeeder::class,
            PermissionSeeder::class,
            StateSeeder::class,
            DestinationSeeder::class,
        ]);

        $adminRole = \App\Models\Rol::where('rol_name', 'admin')->first();
        $pasajeroRole = \App\Models\Rol::where('rol_name', 'pasajero')->first();
        $conductorRole = \App\Models\Rol::where('rol_name', 'conductor')->first();

        // 1. Crear a los usuarios principales PRIMERO para asegurar que Admin sea el ID 1
        User::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('12345678'),
                'rol_id' => $adminRole->id ?? 1,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'pasajero@test.com'],
            [
                'name' => 'Pasajero User',
                'password' => bcrypt('12345678'),
                'rol_id' => $pasajeroRole->id ?? 2,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'conductor@test.com'],
            [
                'name' => 'Conductor User',
                'password' => bcrypt('12345678'),
                'rol_id' => $conductorRole->id ?? 3,
                'email_verified_at' => now(),
            ]
        );

        // 2. Ahora sí, generar la data de prueba (que tomará los IDs a partir del 4)
        $this->call([
            DummyDataSeeder::class,
        ]);
    }
}

