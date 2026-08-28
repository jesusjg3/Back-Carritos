<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Trip;
use App\Models\TripRating;
use App\Models\Destination;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = \App\Models\Rol::where('rol_name', 'admin')->first();
        $pasajeroRole = \App\Models\Rol::where('rol_name', 'pasajero')->first();
        $conductorRole = \App\Models\Rol::where('rol_name', 'conductor')->first();

        // 1. Crear 15 pasajeros adicionales de prueba
        $nombresPasajeros = [
            'Sofía Martínez', 'Alejandro Ruiz', 'Mariana Loo', 'Diego Torres', 'Valentina Silva',
            'Mateo Castro', 'Camila Vargas', 'Nicolás Mendoza', 'Isabella Guerrero', 'Santiago Peralta',
            'Gabriela Peña', 'Lucas Ortiz', 'Lucía Blanco', 'Benjamín Delgado', 'Daniela Romero'
        ];

        $passengers = [];
        foreach ($nombresPasajeros as $index => $nombre) {
            $passengers[] = User::firstOrCreate(
                ['email' => "pasajero" . ($index + 1) . "@test.com"],
                [
                    'name' => $nombre,
                    'password' => bcrypt('12345678'),
                    'rol_id' => $pasajeroRole->id ?? 2,
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );
        }

        // 2. Crear 8 conductores adicionales de prueba
        $nombresConductores = [
            'Juan Pérez', 'Marcos Giménez', 'Luis Herrera', 'Carlos Sánchez',
            'Fernando Díaz', 'Roberto Gómez', 'Eduardo Torres', 'Javier López'
        ];

        $drivers = [];
        foreach ($nombresConductores as $index => $nombre) {
            $drivers[] = User::firstOrCreate(
                ['email' => "conductor" . ($index + 1) . "@test.com"],
                [
                    'name' => $nombre,
                    'password' => bcrypt('12345678'),
                    'rol_id' => $conductorRole->id ?? 3,
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );
        }

        // 3. Crear 2 administradores adicionales
        $nombresAdmins = ['Admin Auxiliar 1', 'Admin Auxiliar 2'];
        foreach ($nombresAdmins as $index => $nombre) {
            User::firstOrCreate(
                ['email' => "admin" . ($index + 1) . "@test.com"],
                [
                    'name' => $nombre,
                    'password' => bcrypt('12345678'),
                    'rol_id' => $adminRole->id ?? 1,
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );
        }

        // 4. Crear 80 Viajes en los últimos 30 días
        $destinations = Destination::all();
        if ($destinations->isEmpty()) {
            return;
        }

        $comentarios = [
            'Excelente servicio, muy rápido.',
            'Conductor muy amable y atento.',
            'Buen viaje, el carrito estaba muy limpio.',
            'Llegamos rápido a nuestro destino.',
            'Súper recomendado.',
            'Muy educado el conductor.',
            'Todo excelente.',
            'Llegó justo a tiempo.',
            'Un viaje agradable y cómodo.',
            'Muy buen trato.',
        ];

        $now = Carbon::now();

        for ($index = 1; $index <= 80; $index++) {
            $passenger = $passengers[array_rand($passengers)];
            $driver = $drivers[array_rand($drivers)];

            // Definir estado
            // 65 terminados, 10 cancelados, 3 iniciados, 2 solicitados
            if ($index <= 65) {
                $stateId = 3; // TERMINADO
            } elseif ($index <= 75) {
                $stateId = 5; // CANCELADO
            } elseif ($index <= 78) {
                $stateId = 4; // INICIADO
            } else {
                $stateId = 1; // SOLICITADO
            }

            // Seleccionar origen y destino aleatorio
            $orig = $destinations->random();
            $dest = $destinations->reject(fn($d) => $d->id === $orig->id)->random();

            if (!$dest) {
                $dest = $orig;
            }

            // Fecha aleatoria dentro de los últimos 30 días
            $diasAtras = rand(0, 30);
            $horaAleatoria = rand(7, 21); // Horario de campus: 7 AM a 9 PM
            $minutoAleatorio = rand(0, 59);
            $fechaViaje = $now->copy()->subDays($diasAtras)->setHour($horaAleatoria)->setMinute($minutoAleatorio);

            $trip = Trip::create([
                'driver_id' => $stateId !== 1 ? $driver->id : null, // Solicitado no tiene conductor asignado aún
                'state_id' => $stateId,
                'origin_lat' => $orig->latitude,
                'origin_lng' => $orig->longitude,
                'origin_address' => $orig->name,
                'destination_lat' => $dest->latitude,
                'destination_lng' => $dest->longitude,
                'destination_address' => $dest->name,
                'distance' => round(rand(100, 1500) / 1000, 2), // distancia entre 0.1km y 1.5km
                'passengers_count' => rand(1, 4),
                'request_attempt' => rand(1, 2),
                'created_at' => $fechaViaje,
                'updated_at' => $fechaViaje->copy()->addMinutes(rand(3, 10)),
            ]);

            // Determine the correct status for the passenger
            $passengerStatus = match ($stateId) {
                1 => 'requested',
                2 => 'accepted',
                3 => 'dropped_off',
                4 => 'boarded',
                5 => 'cancelled',
                default => 'requested',
            };

            // Associate the passenger with the trip
            $trip->passengers()->attach($passenger->id, [
                'status' => $passengerStatus,
            ]);

            // Si está terminado, agregar calificación
            if ($stateId === 3) {
                TripRating::create([
                    'trip_id' => $trip->id,
                    'emitter_id' => $passenger->id,
                    'receiver_id' => $driver->id,
                    'rating' => rand(3, 5), // 3 a 5 estrellas
                    'comment' => rand(0, 10) > 3 ? $comentarios[array_rand($comentarios)] : null,
                    'created_at' => $trip->updated_at,
                    'updated_at' => $trip->updated_at,
                ]);
            }
        }
    }
}
