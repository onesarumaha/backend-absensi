<?php

namespace App\Helpers;

class GeoHelper
{
    /**
     * Hitung jarak antara 2 koordinat dalam meter (Haversine formula)
     */
    public static function distanceInMeters($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000; // meter

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Cek apakah user dalam radius
     */
    public static function isWithinRadius(
        $userLat,
        $userLon,
        $targetLat,
        $targetLon,
        $radiusMeters
    ): bool {
        if (!$targetLat || !$targetLon) {
            return true; // skip validasi kalau target kosong
        }

        $distance = self::distanceInMeters(
            $userLat, $userLon, $targetLat, $targetLon
        );

        return $distance <= $radiusMeters;
    }
}