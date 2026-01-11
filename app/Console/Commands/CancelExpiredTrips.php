<?php

namespace App\Console\Commands;

use App\Models\Trip;
use App\Models\State;
use App\Services\TripService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CancelExpiredTrips extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trips:cancel-expired {--timeout=5}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Cancela solicitudes de viaje que han estado pendientes más del tiempo especificado (en minutos) y las recrea';

    /**
     * Create a new command instance.
     *
     * @var TripService
     */
    protected TripService $tripService;

    public function __construct(TripService $tripService)
    {
        parent::__construct();
        $this->tripService = $tripService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $timeout = (int) $this->option('timeout');
        $this->info("Buscando solicitudes expiradas (timeout: {$timeout} minutos)...");

        try {
            // Buscar solicitudes en estado REQUESTED hace más de X minutos
            $expiredTrips = Trip::where('state_id', State::REQUESTED)
                ->where('created_at', '<=', now()->subMinutes($timeout))
                ->get();

            if ($expiredTrips->isEmpty()) {
                $this->info('No hay solicitudes expiradas.');
                return Command::SUCCESS;
            }

            $this->info("Se encontraron {$expiredTrips->count()} solicitudes expiradas.");

            foreach ($expiredTrips as $trip) {
                try {
                    // Cambiar estado a CANCELLED
                    $trip->update(['state_id' => State::CANCELLED]);

                    // Crear nueva solicitud con los mismos datos
                    $newTrip = $this->tripService->requestTrip([
                        'origin_lat' => $trip->origin_lat,
                        'origin_lng' => $trip->origin_lng,
                        'origin_address' => $trip->origin_address,
                        'destination_lat' => $trip->destination_lat,
                        'destination_lng' => $trip->destination_lng,
                        'destination_address' => $trip->destination_address,
                        'distance' => $trip->distance,
                        'passengers_count' => $trip->passengers_count,
                    ], $trip->passenger);

                    // Actualizar el intento en el nuevo viaje
                    $newTripModel = Trip::find($newTrip['id']);
                    $newAttempt = $trip->request_attempt + 1;
                    $newTripModel->update(['request_attempt' => $newAttempt]);

                    $this->info("✓ Solicitud #{$trip->id} cancelada. Nueva solicitud #{$newTrip['id']} creada (intento {$newAttempt})");
                    
                    Log::info("Trip timeout cancelled", [
                        'original_trip_id' => $trip->id,
                        'new_trip_id' => $newTrip['id'],
                        'passenger_id' => $trip->passenger_id,
                        'attempt' => $newAttempt
                    ]);

                } catch (\Exception $e) {
                    $this->error("✗ Error al procesar solicitud #{$trip->id}: {$e->getMessage()}");
                    Log::error("Trip timeout error", [
                        'trip_id' => $trip->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error en el comando: {$e->getMessage()}");
            Log::error("Cancel expired trips command error", ['error' => $e->getMessage()]);
            return Command::FAILURE;
        }
    }
}
