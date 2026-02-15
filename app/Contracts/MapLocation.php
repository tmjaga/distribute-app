<?php

namespace App\Contracts;

interface MapLocation
{
    /**
     * Generate random coordinates within radius (km) from given point.
     *
     * @param  float  $lat  Given point latitude
     * @param  float  $lng  given point longitude
     * @param  float  $radius  Radius in kilometers
     * @return array [lat, lng]
     */
    public function randomMapLocation(float $lat, float $lng, float $radius = 5): ?array;
}
