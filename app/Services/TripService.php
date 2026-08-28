<?php

namespace App\Services;

use App\Repositories\TripRepository;
use App\Repositories\AssignmentRepository;
use App\Models\Trip;
use App\Models\TripPassenger;
use App\Models\User;
use App\Models\State;
use Illuminate\Support\Facades\DB;
use App\Events\NewTripRequest;
use App\Events\TripTaken;
use App\Events\TripAccepted;
use App\Events\TripStarted;
use App\Events\TripFinished;
use App\Events\TripCancelled;
use App\Events\PassengerBoarded;
use App\Events\PassengerDroppedOff;
use App\Events\PassengerCancelledTrip;
use App\Jobs\SendPushNotificationJob;
use Exception;

class TripService
{
    protected TripRepository $tripRepo;
    protected AssignmentRepository $assignmentRepo;
    protected StatesService $statesService;
    protected ExpoPushService $pushService;

    public function __construct(
        TripRepository $tripRepo,
        AssignmentRepository $assignmentRepo,
        StatesService $statesService, 
        ExpoPushService $pushService
    ) {
        $this->tripRepo = $tripRepo;
        $this->assignmentRepo = $assignmentRepo;
        $this->statesService = $statesService;
        $this->pushService = $pushService;
    }

    public function getAllStates()
    {
        return $this->statesService->getAllStates();
    }

    public function getAllAdminTrips(int $perPage = 10, ?string $search = null, ?string $stateName = null)
    {
        $paginator = $this->tripRepo->getAllWithRelations($perPage, $search, $stateName);
        
        $paginator->getCollection()->transform(function ($trip) {
            return [
                'id' => $trip->id,
                'origin_lat' => $trip->origin_lat,
                'origin_lng' => $trip->origin_lng,
                'origin_address' => $trip->origin_address,
                'destination_lat' => $trip->destination_lat,
                'destination_lng' => $trip->destination_lng,
                'destination_address' => $trip->destination_address,
                'distance' => $trip->distance,
                'passengers_count' => $trip->passengers_count,
                'state_id' => $trip->state_id,
                'created_at' => $trip->created_at,
                'updated_at' => $trip->updated_at,
                'state' => $trip->state ? ['state_name' => $trip->state->state_name] : null,
                'driver' => $trip->driver ? ['name' => $trip->driver->name] : null,
                'passengers' => $trip->passengers->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'pivot' => ['status' => $p->pivot->status]
                    ];
                })->toArray(),
                'ratings' => $trip->ratings->map(function ($r) {
                    return [
                        'id' => $r->id,
                        'rating' => $r->rating,
                        'comment' => $r->comment,
                        'emitter' => $r->emitter ? ['name' => $r->emitter->name] : null
                    ];
                })->toArray(),
            ];
        });

        $stats = $this->tripRepo->getStats($search);

        $result = $paginator->toArray();
        $result['total_registrados'] = $stats['total_registrados'];
        $result['total_terminados'] = $stats['total_terminados'];
        $result['total_cancelados'] = $stats['total_cancelados'];

        return $result;
    }

    public function requestTrip(array $data, User $user)
    {
        return DB::transaction(function () use ($data, $user) {
            if ($this->tripRepo->getCurrentActiveTripForUser($user->id)) {
                throw new Exception('Ya tienes un viaje activo.', 409);
            }
            
            // Buscar viajes activos compartidos excluyendo donde el usuario ya fue rechazado
            $activeTrips = $this->tripRepo->findActiveTripsForRoute($data['destination_address'], $data['passengers_count'] ?? 1, $user->id);
            
            if ($activeTrips->isNotEmpty()) {
                foreach ($activeTrips as $trip) {
                    $driverLocation = $this->tripRepo->getDriverLocation($trip->driver_id);
                    
                    if ($driverLocation && isset($driverLocation['latitude']) && isset($driverLocation['longitude'])) {
                        // Validar con OSRM si el desvío es aceptable
                        $isValidDetour = \App\Helpers\GeoHelper::isDetourValid(
                            $driverLocation['latitude'],
                            $driverLocation['longitude'],
                            $data['origin_lat'],
                            $data['origin_lng'],
                            $trip->destination_lat,
                            $trip->destination_lng
                        );

                        if (!$isValidDetour) {
                            continue; // Ignorar este viaje y probar con el siguiente
                        }
                    }

                    // Verificar si el usuario ya está en este viaje
                    $existingPassenger = $this->tripRepo->getPassengerInTrip($trip->id, $user->id);

                    $pickupData = [
                        'pickup_lat' => $data['origin_lat'] ?? null,
                        'pickup_lng' => $data['origin_lng'] ?? null,
                        'pickup_address' => $data['origin_address'] ?? null,
                        'passengers_count' => $data['passengers_count'] ?? 1,
                    ];

                    if (!$existingPassenger) {
                        $this->tripRepo->addPassengerToTrip($trip->id, $user->id, 'requested', $pickupData);
                    } else if ($existingPassenger->status === 'cancelled') {
                        $this->tripRepo->updatePassengerStatus($trip->id, $user->id, 'requested', $pickupData);
                    }
                    
                    $trip->load(['passengers', 'driver', 'state']);
                    
                    broadcast(new \App\Events\PassengerJoinRequested($trip, $user, $data));
                    return $this->formatTripResponse($trip);
                }
            }

            // Crear uno nuevo si no hay compatibles
            $trip = $this->tripRepo->create(array_merge($data, [
                'state_id' => State::REQUESTED,
            ]));
            
            $pickupData = [
                'pickup_lat' => $data['origin_lat'] ?? null,
                'pickup_lng' => $data['origin_lng'] ?? null,
                'pickup_address' => $data['origin_address'] ?? null,
                'passengers_count' => $data['passengers_count'] ?? 1,
            ];
            $this->tripRepo->addPassengerToTrip($trip->id, $user->id, 'requested', $pickupData);

            $trip->load(['passengers', 'driver', 'state']);
            broadcast(new NewTripRequest($trip));

            return $this->formatTripResponse($trip);
        });
    }

    public function acceptTripById(int $tripId, User $driver, ?int $passengerId = null)
    {
        return DB::transaction(function () use ($tripId, $driver, $passengerId) {
            $trip = $this->tripRepo->findLocked($tripId);

            if ($trip->state_id !== State::REQUESTED) {
                throw new Exception('El viaje ya no está disponible para aceptar.', 409);
            }

            if ($trip->driver_id !== null && $trip->driver_id !== $driver->id) {
                throw new Exception('El viaje ya fue tomado por otro conductor.', 409);
            }

            if ($trip->driver_id === null) {
                $this->tripRepo->update($trip, [
                    'driver_id' => $driver->id,
                    'state_id' => State::ACCEPTED,
                    'accepted_at' => now(),
                ]);
            }
            
            if ($passengerId) {
                $passenger = $this->tripRepo->getPassengerInTrip($tripId, $passengerId);
                if (!$passenger || $passenger->status !== TripPassenger::STATUS_REQUESTED) {
                    throw new Exception('La solicitud del pasajero ya no es válida.', 400);
                }
                $this->tripRepo->updatePassengerStatus($tripId, $passengerId, TripPassenger::STATUS_ACCEPTED);
            } else {
                $accepted = $this->tripRepo->updateAllPassengersStatus($tripId, TripPassenger::STATUS_REQUESTED, TripPassenger::STATUS_ACCEPTED);
                if ($accepted === 0) {
                    throw new Exception('El viaje no tiene solicitudes pendientes.', 409);
                }
            }

            $trip->load(['passengers', 'driver', 'state']);

            broadcast(new TripTaken($trip));
            broadcast(new TripAccepted($trip));

            // Notify passengers
            foreach ($trip->passengers as $p) {
                SendPushNotificationJob::dispatch(
                    $p->id,
                    "Viaje Aceptado",
                    "El conductor {$driver->name} ha aceptado tu viaje."
                );
            }

            return $this->formatTripResponse($trip);
        });
    }

    public function acceptTrip(Trip $trip, User $driver)
    {
        return $this->acceptTripById($trip->id, $driver);
    }

    public function acceptPassengerInTrip(int $tripId, User $driver, int $passengerId)
    {
        return DB::transaction(function () use ($tripId, $driver, $passengerId) {
            $trip = $this->tripRepo->findLocked($tripId);

            if ($trip->driver_id !== $driver->id) {
                throw new \Exception('No autorizado para gestionar este viaje.', 403);
            }

            if (!in_array($trip->state_id, [State::ACCEPTED, State::STARTED])) {
                throw new \Exception('El viaje no está activo.', 400);
            }

            $existingPassenger = $this->tripRepo->getPassengerInTrip($tripId, $passengerId);
            if (!$existingPassenger || $existingPassenger->status !== TripPassenger::STATUS_REQUESTED) {
                throw new \Exception('La solicitud del pasajero ya no es válida.', 400);
            }

            // Validar capacidad usando el repositorio correspondiente
            $driverAssignment = $this->assignmentRepo->getActiveAssignmentByUserId($driver->id);
                
            if (!$driverAssignment || !$driverAssignment->vehicle) {
                throw new \Exception('No tienes un vehículo asignado activo.', 400);
            }

            $capacity = $driverAssignment->vehicle->capacity;
            $activePassengers = $this->tripRepo->getActivePassengersCount($tripId);
            $newPassengersCount = $existingPassenger->passengers_count ?? 1;

            if (($activePassengers + $newPassengersCount) > $capacity) {
                throw new \Exception('Capacidad máxima del vehículo excedida.', 400);
            }

            $this->tripRepo->updatePassengerStatus($tripId, $passengerId, TripPassenger::STATUS_ACCEPTED);

            $trip->load(['passengers', 'driver', 'state']);

            // Notificar solo al pasajero que fue aceptado
            broadcast(new TripAccepted($trip, $passengerId));
            
            SendPushNotificationJob::dispatch(
                $passengerId,
                "Viaje Aceptado",
                "El conductor {$driver->name} ha aceptado tu viaje."
            );

            return $this->formatTripResponse($trip);
        });
    }

    public function startTrip(int $tripId, User $user)
    {
        return DB::transaction(function () use ($tripId, $user) {
            $trip = $this->tripRepo->findLocked($tripId);
            
            if ($trip->driver_id !== $user->id) {
                throw new Exception('No autorizado para iniciar este viaje.', 403);
            }

            if ($trip->state_id === State::STARTED) {
                $trip->load(['passengers', 'driver', 'state']);
                return $this->formatTripResponse($trip);
            }

            if ($trip->state_id !== State::ACCEPTED) {
                throw new Exception('Solo se puede iniciar un viaje aceptado.', 400);
            }

            $trip = $this->tripRepo->update($trip, [
                'state_id' => State::STARTED,
                'started_at' => now(),
            ]);

            // Auto-board all accepted passengers to avoid skipping the boarding phase
            $this->tripRepo->updateAllPassengersStatus($tripId, 'accepted', 'boarded');

            $trip->load(['passengers', 'driver', 'state']);
            broadcast(new TripStarted($trip));

            // Notify passengers
            foreach ($trip->passengers as $p) {
                SendPushNotificationJob::dispatch(
                    $p->id,
                    "Viaje Iniciado",
                    "El conductor ha iniciado la ruta hacia el destino."
                );
            }

            return $this->formatTripResponse($trip);
        });
    }
    
    public function boardPassenger(int $tripId, int $passengerId, User $driver)
    {
        return DB::transaction(function () use ($tripId, $passengerId, $driver) {
            $trip = $this->tripRepo->findLocked($tripId);
            
            if ($trip->driver_id !== $driver->id) {
                throw new Exception('No autorizado.', 403);
            }

            if ($trip->state_id !== State::STARTED) {
                throw new Exception('El viaje debe estar iniciado para abordar pasajeros.', 400);
            }

            $passenger = $this->tripRepo->getPassengerInTrip($trip->id, $passengerId);
            if (!$passenger || $passenger->status !== TripPassenger::STATUS_ACCEPTED) {
                throw new Exception('El pasajero no tiene una solicitud aceptada para abordar.', 400);
            }
            
            $this->tripRepo->updatePassengerStatus($trip->id, $passengerId, TripPassenger::STATUS_BOARDED);
                
            $trip->load(['passengers', 'driver', 'state']);
            broadcast(new PassengerBoarded($trip, $passengerId));

            return $this->formatTripResponse($trip);
        });
    }

    public function dropOffPassenger(int $tripId, int $passengerId, User $driver)
    {
        return DB::transaction(function () use ($tripId, $passengerId, $driver) {
            $trip = $this->tripRepo->findLocked($tripId);
            
            if ($trip->driver_id !== $driver->id) {
                throw new Exception('No autorizado.', 403);
            }

            if ($trip->state_id !== State::STARTED) {
                throw new Exception('El viaje debe estar iniciado para bajar pasajeros.', 400);
            }

            $passenger = $this->tripRepo->getPassengerInTrip($trip->id, $passengerId);
            if (!$passenger || $passenger->status !== TripPassenger::STATUS_BOARDED) {
                throw new Exception('El pasajero no está abordo en este viaje.', 400);
            }
            
            $this->tripRepo->updatePassengerStatus($trip->id, $passengerId, TripPassenger::STATUS_DROPPED_OFF);
                
            $trip->load(['passengers', 'driver', 'state']);
            broadcast(new PassengerDroppedOff($trip, $passengerId));

            return $this->formatTripResponse($trip);
        });
    }

    public function cancelPassenger(int $tripId, int $passengerId, User $driver)
    {
        return DB::transaction(function () use ($tripId, $passengerId, $driver) {
            $trip = $this->tripRepo->findLocked($tripId);
            
            if ($trip->driver_id !== $driver->id) {
                throw new Exception('No autorizado para cancelar este pasajero.', 403);
            }

            if (!in_array($trip->state_id, [State::ACCEPTED, State::STARTED], true)) {
                throw new Exception('El viaje no permite cancelar pasajeros.', 400);
            }

            $passenger = $this->tripRepo->getPassengerInTrip($trip->id, $passengerId);
            if (!$passenger || !in_array($passenger->status, [TripPassenger::STATUS_REQUESTED, TripPassenger::STATUS_ACCEPTED], true)) {
                throw new Exception('El pasajero no tiene una solicitud cancelable.', 400);
            }
            
            // Marcar solo al pasajero como cancelado
            $this->tripRepo->updatePassengerStatus($trip->id, $passengerId, TripPassenger::STATUS_CANCELLED);
            
            broadcast(new PassengerCancelledTrip($trip, $passengerId));

            // Si ya no quedan pasajeros activos (todos bajaron o fueron cancelados), cancelar el viaje
            $activePassengers = $this->tripRepo->getActivePassengersCount($trip->id);
            if ($activePassengers === 0 && $trip->state_id !== State::FINISHED && $trip->state_id !== State::CANCELLED) {
                $this->tripRepo->update($trip, ['state_id' => State::CANCELLED]);
                
                // Cancel all remaining passengers in this trip
                $this->tripRepo->updateAllPassengersStatus($trip->id, TripPassenger::STATUS_REQUESTED, TripPassenger::STATUS_CANCELLED);
                $this->tripRepo->updateAllPassengersStatus($trip->id, TripPassenger::STATUS_ACCEPTED, TripPassenger::STATUS_CANCELLED);
                $this->tripRepo->updateAllPassengersStatus($trip->id, TripPassenger::STATUS_BOARDED, TripPassenger::STATUS_CANCELLED);

                $trip->load(['passengers', 'driver', 'state']);
                broadcast(new TripCancelled($trip, [$passengerId]));
            } else {
                $trip->load(['passengers', 'driver', 'state']);
                // Se podría emitir un evento específico, pero el polling o re-render
                // de ActiveTripCard lo actualizará. Opcionalmente usar TripLocationUpdated
            }

            // Notificar al pasajero
            SendPushNotificationJob::dispatch(
                $passengerId,
                "Viaje Cancelado",
                "El conductor ha cancelado tu asignación en este viaje."
            );

            return $this->formatTripResponse($trip);
        });
    }

    public function rejectPassenger(int $tripId, int $passengerId, User $driver)
    {
        return DB::transaction(function () use ($tripId, $passengerId, $driver) {
            $trip = $this->tripRepo->findLocked($tripId);
            
            if ($trip->driver_id !== $driver->id) {
                throw new Exception('No autorizado para rechazar a este pasajero.', 403);
            }

            if (!in_array($trip->state_id, [State::ACCEPTED, State::STARTED], true)) {
                throw new Exception('El viaje no permite rechazar pasajeros.', 400);
            }

            $passenger = $this->tripRepo->getPassengerInTrip($tripId, $passengerId);
            if (!$passenger || $passenger->status !== TripPassenger::STATUS_REQUESTED) {
                throw new Exception('La solicitud del pasajero ya no es válida.', 400);
            }
            
            // Marcar al pasajero como cancelado
            $this->tripRepo->updatePassengerStatus($trip->id, $passengerId, TripPassenger::STATUS_CANCELLED);
            
            // Usamos un flag especial o enviamos un evento con una razón
            // En este caso PassengerCancelledTrip ya tiene trip y passengerId.
            // Para distinguirlo, pasaremos un extra.
            broadcast(new PassengerCancelledTrip($trip, $passengerId, 'auto_retry'));

            $trip->load(['passengers', 'driver', 'state']);
            return $this->formatTripResponse($trip);
        });
    }

    public function finishTrip(int $tripId, User $user)
    {
        return DB::transaction(function () use ($tripId, $user) {
            $trip = $this->tripRepo->findLocked($tripId);
            
            if ($trip->driver_id !== $user->id) {
                throw new Exception('No autorizado para finalizar este viaje.', 403);
            }

            if ($trip->state_id !== State::STARTED) {
                throw new Exception('Solo se puede finalizar un viaje iniciado.', 400);
            }

            $trip = $this->tripRepo->update($trip, [
                'state_id' => State::FINISHED,
                'finished_at' => now(),
            ]);
            
            // Drop off boarded passengers
            $this->tripRepo->updateAllPassengersStatus($trip->id, TripPassenger::STATUS_BOARDED, TripPassenger::STATUS_DROPPED_OFF);
            
            // Cancel any remaining requested/accepted passengers who never boarded
            $this->tripRepo->updateAllPassengersStatus($trip->id, TripPassenger::STATUS_REQUESTED, TripPassenger::STATUS_CANCELLED);
            $this->tripRepo->updateAllPassengersStatus($trip->id, TripPassenger::STATUS_ACCEPTED, TripPassenger::STATUS_CANCELLED);

            $trip->load(['passengers', 'driver', 'state']);
            broadcast(new TripFinished($trip));

            foreach ($trip->passengers as $p) {
                if ($p->pivot && $p->pivot->status === TripPassenger::STATUS_DROPPED_OFF) {
                    SendPushNotificationJob::dispatch(
                        $p->id,
                        "Viaje Finalizado",
                        "Has llegado a tu destino. ¡Gracias por usar Carritos!"
                    );
                }
            }

            return $this->formatTripResponse($trip);
        });
    }

    public function cancelTrip(int $tripId, User $user, ?string $reason = null)
    {
        return DB::transaction(function () use ($tripId, $user, $reason) {
            $trip = $this->tripRepo->findLocked($tripId);

            if ($trip->state_id === State::FINISHED || $trip->state_id === State::CANCELLED) {
                return $this->formatTripResponse($trip);
            }

            if (!$user->relationLoaded('rol')) {
                $user->load('rol');
            }
            $roleName = $user->rol->rol_name;

            if ($roleName === 'pasajero') {
                $passenger = $this->tripRepo->getPassengerInTrip($tripId, $user->id);
                if (!$passenger || !in_array($passenger->status, [TripPassenger::STATUS_REQUESTED, TripPassenger::STATUS_ACCEPTED, TripPassenger::STATUS_BOARDED], true)) {
                    throw new Exception('No perteneces a un viaje activo con este identificador.', 403);
                }

                $this->tripRepo->updatePassengerStatus($tripId, $user->id, 'cancelled');
                    
                $activePassengers = $this->tripRepo->getActivePassengersCount($tripId);
                    
                if ($activePassengers === 0) {
                    $this->tripRepo->update($trip, ['state_id' => State::CANCELLED]);
                } else {
                    // Notificar al conductor que este pasajero canceló
                    if ($trip->driver_id) {
                        broadcast(new \App\Events\PassengerCancelledTrip($trip, $user->id));
                        SendPushNotificationJob::dispatch(
                            $trip->driver_id,
                            "Pasajero Canceló",
                            "Un pasajero ha cancelado su solicitud."
                        );
                    }
                    return $this->formatTripResponse($trip);
                }
            } else if ($roleName === 'conductor') {
                if ($trip->driver_id !== $user->id) {
                    throw new Exception('No autorizado para cancelar este viaje.', 403);
                }

                if (!in_array($trip->state_id, [State::ACCEPTED, State::STARTED], true)) {
                    throw new Exception('El viaje no permite cancelación en este estado.', 400);
                }
                
                if (empty($reason)) {
                    throw new Exception('El motivo de cancelación es obligatorio para el conductor.', 400);
                }
                
                $this->tripRepo->update($trip, [
                    'state_id' => State::CANCELLED,
                    'cancel_reason' => $reason
                ]);

                $autoRetryIds = [];
                foreach ($trip->passengers as $p) {
                    $status = $p->pivot->status ?? 'cancelled';
                    if (in_array($status, ['requested', 'accepted'])) {
                        $autoRetryIds[] = $p->id;
                        broadcast(new PassengerCancelledTrip($trip, $p->id, 'auto_retry'));
                    }
                }

                $this->tripRepo->cancelAllPassengers($tripId);
            }

            $trip->load(['passengers', 'driver', 'state']);
            $excludedIds = isset($autoRetryIds) ? $autoRetryIds : [];
            broadcast(new TripCancelled($trip, $excludedIds));

            if ($roleName === 'pasajero' && $trip->driver_id) {
                SendPushNotificationJob::dispatch(
                    $trip->driver_id,
                    "Viaje Cancelado",
                    "Un pasajero ha cancelado su solicitud."
                );
            } else if ($roleName === 'conductor') {
                foreach ($trip->passengers as $p) {
                    SendPushNotificationJob::dispatch(
                        $p->id,
                        "Viaje Cancelado",
                        "El conductor ha cancelado el viaje."
                    );
                }
            }

            return $this->formatTripResponse($trip);
        });
    }

    private function formatTripResponse(Trip $trip): array
    {
        $driverLocation = null;
        if ($trip->driver_id) {
            $driverLocation = $this->tripRepo->getDriverLocation($trip->driver_id);
        }

        return [
            'id' => $trip->id,
            'origin_lat' => $trip->origin_lat,
            'origin_lng' => $trip->origin_lng,
            'origin_address' => $trip->origin_address,
            'destination_lat' => $trip->destination_lat,
            'destination_lng' => $trip->destination_lng,
            'destination_address' => $trip->destination_address,
            'distance' => $trip->distance,
            'passengers_count' => $trip->passengers_count,
            'request_attempt' => $trip->request_attempt,
            'cancel_reason' => $trip->cancel_reason,
            'state_id' => $trip->state_id,
            'state' => $trip->state ? [
                'id' => $trip->state->id,
                'name' => $trip->state->state_name
            ] : null,
            'passengers' => $trip->passengers->map(function ($passenger) {
                return [
                    'id' => $passenger->id,
                    'name' => $passenger->name,
                    'phone' => $passenger->phone,
                    'status' => $passenger->pivot->status ?? 'requested',
                    'pickup_lat' => $passenger->pivot->pickup_lat ?? null,
                    'pickup_lng' => $passenger->pivot->pickup_lng ?? null,
                    'pickup_address' => $passenger->pivot->pickup_address ?? null,
                    'passengers_count' => $passenger->pivot->passengers_count ?? 1,
                ];
            })->toArray(),
            'driver' => $trip->driver ? [
                'id' => $trip->driver->id,
                'name' => $trip->driver->name,
                'phone' => $trip->driver->phone,
                'rating' => $trip->driver->ratingProfile->score ?? 5.0,
                'score' => $trip->driver->ratingProfile->score ?? 5.0,
                'rating_count' => $trip->driver->ratingProfile->rating_count ?? 0,
                'latitude' => $driverLocation['latitude'] ?? null,
                'longitude' => $driverLocation['longitude'] ?? null,
                'last_update' => $driverLocation['last_update'] ?? null,
                'location' => $driverLocation,
            ] : null,
        ];
    }

    public function getTripHistory(User $user)
    {
        if (!$user->relationLoaded('rol')) {
            $user->load('rol');
        }

        $trips = $user->rol?->rol_name === 'conductor'
            ? $this->tripRepo->getByDriver($user->id)
            : $this->tripRepo->getByPassenger($user->id);
        $trips->load(['driver', 'state']);

        $trips->load([
            'ratings' => function ($query) use ($user) {
                $query->where('emitter_id', $user->id);
            }
        ]);

        return $trips->map(function ($trip) {
            $myRating = $trip->ratings->first();
            return [
                'id' => $trip->id,
                'origin_address' => $trip->origin_address,
                'destination_address' => $trip->destination_address,
                'state' => $trip->state ? $trip->state->state_name : 'Unknown',
                'created_at' => $trip->created_at,
                'driver' => $trip->driver ? ['name' => $trip->driver->name] : null,
                'my_rating' => $myRating ? [
                    'rating' => $myRating->rating,
                    'comment' => $myRating->comment
                ] : null
            ];
        });
    }

    public function getCurrentActiveTrip(User $user)
    {
        $trip = $this->tripRepo->getCurrentActiveTripForUser($user->id);
        
        if ($trip) {
            $trip->load(['passengers', 'driver', 'state']);
            return $this->formatTripResponse($trip);
        }

        return null;
    }
}
