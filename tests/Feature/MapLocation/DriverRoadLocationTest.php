<?php


use App\Services\DriverRoadsLocationService;
use Illuminate\Support\Facades\Redis;
use MessagePack\Packer;

use function Tests\Helpers\runMapLocationContractTests;

beforeEach(function () {
    Redis::flushdb();

    // Happy Victoria, Sofia
    $lat = 42.688600;
    $lng = 23.308027;

    seedRoadSegment($lat, $lng, $lat + 0.01, $lng + 0.01);
    seedRoadSegment($lat, $lng, $lat - 0.01, $lng - 0.01);
});

runMapLocationContractTests(new DriverRoadsLocationService);

if (! function_exists('seedRoadSegment')) {
    function seedRoadSegment(float $lat1, float $lng1, float $lat2, float $lng2)
    {
        $tileSize = 0.02;
        $tileKey = floor($lat1 / $tileSize).'_'.floor($lng1 / $tileSize);

        $packer = new Packer;

        Redis::set(
            'roads:'.$tileKey,
            $packer->pack([
                [$lat1, $lng1, $lat2, $lng2],
            ])
        );
    }
}
