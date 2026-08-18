<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoHelper
{
    /**
     * OSRM Server URL
     */
    const OSRM_URL = 'http://192.168.10.96:5000/route/v1/driving/';

    /**
     * Verifica si el desvío es válido para Carpooling consultando a OSRM.
     * Un desvío es válido si no incrementa el tiempo y la distancia excesivamente.
     * Como es un campus universitario, los límites son muy estrictos.
     * 
     * @param float $driverLat
     * @param float $driverLng
     * @param float $pickupLat
     * @param float $pickupLng
     * @param float $destLat
     * @param float $destLng
     * @return bool True si el desvío es válido, False si el conductor ya se pasó o está muy lejos.
     */
    public static function isDetourValid($driverLat, $driverLng, $pickupLat, $pickupLng, $destLat, $destLng): bool
    {
        try {
            // 1. Ruta Directa (Conductor -> Destino Final)
            $directUrl = self::OSRM_URL . "{$driverLng},{$driverLat};{$destLng},{$destLat}?overview=false";
            $directResponse = Http::timeout(3)->get($directUrl);

            // 2. Ruta con Desvío (Conductor -> Pasajero B -> Destino Final)
            $detourUrl = self::OSRM_URL . "{$driverLng},{$driverLat};{$pickupLng},{$pickupLat};{$destLng},{$destLat}?overview=false";
            $detourResponse = Http::timeout(3)->get($detourUrl);

            if (!$directResponse->successful() || !$detourResponse->successful()) {
                Log::warning('OSRM API Falló. Usando validación estricta por defecto.');
                return false; // Ante la duda, no agrupar para no arruinar el viaje del conductor
            }

            $directData = $directResponse->json();
            $detourData = $detourResponse->json();

            if (!isset($directData['routes'][0]) || !isset($detourData['routes'][0])) {
                return false;
            }

            $directRoute = $directData['routes'][0];
            $detourRoute = $detourData['routes'][0];

            $directDistance = $directRoute['distance']; // Metros
            $directDuration = $directRoute['duration']; // Segundos

            $detourDistance = $detourRoute['distance'];
            $detourDuration = $detourRoute['duration'];

            $extraDistance = $detourDistance - $directDistance;
            $extraDuration = $detourDuration - $directDuration;

            // Límites para un Campus Universitario (Muy Cerrado)
            // Máximo 600 metros de desvío extra y 4 minutos extra
            $maxExtraDistanceMeters = 600;
            $maxExtraDurationSeconds = 240;

            if ($extraDistance > $maxExtraDistanceMeters || $extraDuration > $maxExtraDurationSeconds) {
                Log::info("Carpooling Rechazado. Desvío extra: {$extraDistance}m, {$extraDuration}s");
                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Error validando desvío geoespacial: ' . $e->getMessage());
            return false;
        }
    }
}
