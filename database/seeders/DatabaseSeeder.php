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
    }
}

