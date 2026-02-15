<?php

namespace App\Console\Commands;

use App\Services\DriverRoadsLocationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use JsonMachine\Items;
use MessagePack\Packer;

class RoadsPreloadCommand extends Command
{
    private const CACHE_PREFIX = 'roads:';
    private const TILE_SIZE = 0.02;

    protected $signature = 'roads:preload';

    protected $description = 'Load roads JSON into Redis tiles';

    public function handle(): int
    {
        $this->preloadSegmentsToCacheMsgPack();

        if (Cache::has('roads:preloaded')) {
            $this->info('Preload Compleated');
        }

        return self::SUCCESS;
    }

    /**
     * Preload road segments into Redis using MsgPack.
     */
    private function preloadSegmentsToCacheMsgPack(): void
    {
        $this->clearOldCache();

        $buffer = [];
        $bufferLimit = 1000;
        $packer = new Packer;

        foreach ($this->getSpatialIndexStream() as $tileKey => $segment) {
            $buffer[$tileKey][] = $segment;

            if (count($buffer) >= $bufferLimit) {
                $this->flushBufferToRedis($buffer, $packer);
                $buffer = [];
            }
        }

        if ($buffer) {
            $this->flushBufferToRedis($buffer, $packer);
        }

        Cache::forever('roads:preloaded', true);
    }

    /**
     * Clear all old road segment data from Redis.
     */
    private function clearOldCache(): void
    {
        $keys = Redis::keys(self::CACHE_PREFIX.'*');
        if ($keys) {
            Redis::del($keys);
        }

        // Cache::forget(self::PRELOAD_FLAG);
    }

    /**
     * Write buffered segments to Redis using pipeline.
     *
     * @param  array<string, array>  $tiles
     */
    private function flushBufferToRedis(array $tiles, Packer $packer): void
    {
        Redis::pipeline(function ($pipe) use ($tiles, $packer) {
            foreach ($tiles as $tileKey => $segments) {
                $pipe->set(self::CACHE_PREFIX.$tileKey, $packer->pack($segments));
            }
        });
    }

    /**
     * Stream road segments from JSON file.
     *
     * @return iterable<string, array> tileKey => segment
     */
    private function getSpatialIndexStream(): iterable
    {
        $roads = Items::fromFile(storage_path('app/private/sofia_roads.json'));

        foreach ($roads as $road) {
            for ($i = 0; $i < count($road) - 1; $i++) {
                [$lng1, $lat1] = $road[$i];
                [$lng2, $lat2] = $road[$i + 1];

                $segment = [$lat1, $lng1, $lat2, $lng2];

                foreach ($this->segmentTiles($lat1, $lng1, $lat2, $lng2) as $tileKey) {
                    yield $tileKey => $segment;
                }
            }
        }
    }

    /**
     * Get tile keys covered by a segment.
     *
     * @return array<int, string>
     */
    private function segmentTiles(float $lat1, float $lng1, float $lat2, float $lng2): array
    {
        return [
            $this->tileKey($lat1, $lng1),
            $this->tileKey($lat2, $lng2),
        ];
    }

    /**
     * Convert latitude/longitude to tile key.
     */
    private function tileKey(float $lat, float $lng): string
    {
        return floor($lat / self::TILE_SIZE).'_'.floor($lng / self::TILE_SIZE);
    }
}
