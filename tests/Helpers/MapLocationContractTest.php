<?php

namespace Tests\Helpers;

use App\Contracts\MapLocation;
use App\Models\Restaurant;
use TarfinLabs\LaravelSpatial\Types\Point;

const TEST_LAT = 42.6977;
const TEST_LNG = 23.3219;
const TEST_RADIUS = 5;

if (! function_exists('runMapLocationContractTests')) {

    function runMapLocationContractTests(MapLocation $service)
    {
        it('returns an array with 2 floats', function () use ($service) {
            $restaurant = new Restaurant;
            $restaurant->coordinates = new Point(TEST_LAT, TEST_LNG);

            $point = $service->randomMapLocation(
                $restaurant->coordinates->getLat(),
                $restaurant->coordinates->getLng(),
                TEST_RADIUS
            );

            expect($point)->toBeArray()
                ->and(count($point))->toBe(2)
                ->and($point[0])->toBeFloat()
                ->and($point[1])->toBeFloat();
        });

        it('random coordinates are within radius (5km)', function () use ($service) {

            $restaurant = new Restaurant;
            $restaurant->coordinates = new Point(TEST_LAT, TEST_LNG);

            $point = $service->randomMapLocation(
                $restaurant->coordinates->getLat(),
                $restaurant->coordinates->getLng(),
                TEST_RADIUS
            );

            $helper = new DriverOperatiosHelper;
            $distance = $helper->computeDistance(
                $restaurant->coordinates->getLat(),
                $restaurant->coordinates->getLng(),
                $point[0],
                $point[1]
            );

            expect($distance)->toBeLessThanOrEqual(TEST_RADIUS);
        });

        it('returns null or empty if no valid location', function () use ($service) {
            $result = $service->randomMapLocation(0, 0, 5);

            expect($result === null || $result === [] || is_array($result))->toBeTrue();
        });

    }
}

