<?php

namespace App\Services;

use App\Contracts\MapLocation;
use App\Traits\DriverOperations;
use Illuminate\Support\Facades\Http;

class DriverOsrmLocationService implements MapLocation
{
    use DriverOperations;

    public function randomMapLocation(float $lat, float $lng, float $radius = 5): array
    {
        do {
            [$newLat, $newLng] = $this->getRandomLocation($lat, $lng);
            $road = $this->bindToRoad($newLat, $newLng);
        } while (! $road);

        return $road;
    }

    private function bindToRoad(float $lat, float $lng): ?array
    {
        $response = Http::get(
            "https://router.project-osrm.org/nearest/v1/driving/$lng,$lat"
        );

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        if (! isset($data['waypoints'][0]['location'])) {
            return null;
        }

        return [
            $data['waypoints'][0]['location'][1],
            $data['waypoints'][0]['location'][0],
        ];
    }
}
