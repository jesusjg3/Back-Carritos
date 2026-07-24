<?php

namespace App\Services;

class LocationService
{
    /**
     * Calculate the distance in kilometers between two sets of coordinates using the Haversine formula.
     *
     * @param float $lat1
     * @param float $lng1
     * @param float $lat2
     * @param float $lng2
     * @return float Distance in kilometers
     */
    public function calculateDistanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371;

        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($deltaLng / 2) ** 2;

        return 2 * $earthRadiusKm * asin(min(1, sqrt($a)));
    }
}
