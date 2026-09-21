<?php

namespace App\Http\Requests\Concerns;

use App\Helpers\GeoHelper;

trait ValidatesRadius
{
    /**
     * Validasi radius. Return null kalau OK, pesan error kalau gagal.
     */
    protected function validateRadius($latitude, $longitude): ?string
    {
        $user = $this->user();
        if (!$user) return null;

        $employee = $user->employee;
        if (!$employee) return null;

        $schedule = $employee->workSchedule;
        if (!$schedule) return null;

        // Kalau work schedule tidak punya koordinat, skip validasi
        if (!$schedule->latitude || !$schedule->longitude) {
            return null;
        }

        $radius = $schedule->radius_meters ?? 100;

        $isWithin = GeoHelper::isWithinRadius(
            $latitude,
            $longitude,
            $schedule->latitude,
            $schedule->longitude,
            $radius
        );

        if (!$isWithin) {
            $distance = round(GeoHelper::distanceInMeters(
                $latitude,
                $longitude,
                $schedule->latitude,
                $schedule->longitude
            ));

            return "Anda berada di luar radius absen ({$distance}m dari kantor, maksimal {$radius}m).";
        }

        return null;
    }
}
