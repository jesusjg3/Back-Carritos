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
        ]);

        $pasajeroRole = \App\Models\Rol::where('rol_name', 'pasajero')->first();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'rol_id' => $pasajeroRole->id ?? 1,
        ]);
    }
}
