<?php

namespace App\Services;

use App\Contracts\MapLocation;
use App\Traits\DriverOperations;
use Illuminate\Support\Facades\Http;

class DriverLocationService implements MapLocation
{
    use DriverOperations;

    public function randomMapLocation(float $lat, float $lng, float $radius = 5): array
    {
        return $this->getRandomLocation($lat, $lng, $radius);
    }
}
