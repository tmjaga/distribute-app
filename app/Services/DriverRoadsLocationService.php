<?php

namespace App\Services;

use App\Contracts\MapLocation;
use Illuminate\Support\Facades\Redis;
use MessagePack\BufferUnpacker;

class DriverRoadsLocationService implements MapLocation
{
    public const TILE_SIZE = 0.02;

    private const CACHE_PREFIX = 'roads:';

    /**
     * Get a random location on a road near the given point within a radius.
     *
     * @param  float  $lat  Latitude of the center point
     * @param  float  $lng  Longitude of the center point
     * @param  float  $radius  Radius in km
     * @return array ['lat' => float, 'lng' => float] or empty array if no road found
     */
    public function randomMapLocation(float $lat, float $lng, float $radius = 5): array
    {
        $tileKeys = $this->getTilesAroundPoint($lat, $lng);
        shuffle($tileKeys);

        $chosenSegment = null;
        $count = 0;

        $maxSegmentsToCheck = 100;
        $checked = 0;

        $unpacker = new BufferUnpacker;

        foreach ($tileKeys as $tileKey) {
            $packed = Redis::get(self::CACHE_PREFIX.$tileKey);
            if (! $packed) {
                continue;
            }

            $unpacker->reset($packed);
            $segments = $unpacker->unpack();

            foreach ($segments as $segment) {
                if ($this->pointSegmentDistance($lat, $lng, ...$segment) <= $radius) {
                    $count++;
                    if (mt_rand(1, $count) === 1) {
                        $chosenSegment = $segment;
                    }

                    $checked++;
                    if ($checked >= $maxSegmentsToCheck) {
                        break 2;
                    }
                }
            }
        }

        if (! $chosenSegment) {
            return [];
        }

        [$lat1, $lng1, $lat2, $lng2] = $chosenSegment;
        $t = mt_rand(0, 1000) / 1000;

        $lat = $lat1 + ($lat2 - $lat1) * $t;
        $lng = $lng1 + ($lng2 - $lng1) * $t;

        return [$lat, $lng];
    }

    /**
     * Lazily yield segments around a given point within a radius.
     *
     * @return \Generator<array>
     */
    private function getSegmentsAroundPoint(float $lat, float $lng, float $radiusKm): \Generator
    {
        $tileKeys = $this->getTilesAroundPoint($lat, $lng);
        $unpacker = new BufferUnpacker;

        foreach ($tileKeys as $tileKey) {
            $packed = Redis::get(self::CACHE_PREFIX.$tileKey);
            if (! $packed) {
                continue;
            }

            $unpacker->reset($packed);
            $segments = $unpacker->unpack();

            foreach ($segments as $segment) {
                if ($this->pointSegmentDistance($lat, $lng, ...$segment) <= $radiusKm) {
                    yield $segment;
                }
            }
        }
    }

    /**
     * Get surrounding tile keys for a point.
     *
     * @return array<int, string>
     */
    private function getTilesAroundPoint(float $lat, float $lng): array
    {
        $baseLat = floor($lat / self::TILE_SIZE);
        $baseLng = floor($lng / self::TILE_SIZE);

        $tiles = [];
        for ($i = -2; $i <= 2; $i++) {
            for ($j = -2; $j <= 2; $j++) {
                $tiles[] = ($baseLat + $i).'_'.($baseLng + $j);
            }
        }

        return $tiles;
    }

    /**
     * Calculate distance from a point to a line segment.
     */
    private function pointSegmentDistance(
        float $lat, float $lng,
        float $lat1, float $lng1,
        float $lat2, float $lng2
    ): float {
        $dx = $lng2 - $lng1;
        $dy = $lat2 - $lat1;

        if ($dx === 0 && $dy === 0) {
            return sqrt(($lat - $lat1) ** 2 + ($lng - $lng1) ** 2);
        }

        $t = (($lat - $lat1) * $dy + ($lng - $lng1) * $dx) / ($dx * $dx + $dy * $dy);
        $t = max(0, min(1, $t));

        $projLat = $lat1 + $t * $dy;
        $projLng = $lng1 + $t * $dx;

        return sqrt(($lat - $projLat) ** 2 + ($lng - $projLng) ** 2);
    }
}
