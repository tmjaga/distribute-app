<?php

use App\Models\Driver;
use App\Models\Restaurant;
use App\Services\GreedyDistributorService;
use App\Services\HungarianDistributorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use TarfinLabs\LaravelSpatial\Types\Point;

uses(RefreshDatabase::class);

beforeEach(function () {
    $ordersCounts = [50, 45, 40, 35, 30, 25, 20, 15, 10, 5];

    $restaurants = Restaurant::factory()
        ->count(10)
        ->sequence(fn ($sequence) => [
            'orders_count' => $ordersCounts[$sequence->index],
            'coordinates' => new Point(
                40.0 + $sequence->index * 0.01, // fixed coordinates
                -74.0 + $sequence->index * 0.01
            ),
        ])
        ->create();

    // 100 drivers with capacity, so total capacity almost covers all orders
    $capacities = array_merge(
        array_fill(0, 50, 3),
        array_fill(0, 50, 2)
    );

    foreach (range(0, 99) as $i) {
        $restaurantIndex = $i % 10; // driver “assigned” to restaurant by index
        $restaurant = $restaurants[$restaurantIndex];

        Driver::factory()->create([
            'capacity' => $capacities[$i],
            'current_coordinates' => new Point(
                $restaurant->coordinates->getLat() + rand(-5, 5) / 1000, // small offset
                $restaurant->coordinates->getLng() + rand(-5, 5) / 1000
            ),
        ]);
    }
});

it('greedy distributor leaves realistic orders_after', function () {
    $service = new GreedyDistributorService;
    $service->distribute();

    $restaurants = Restaurant::all();

    $ordersAfter = [];
    foreach ($restaurants as $restaurant) {
        $assignedCapacity = $restaurant->drivers->sum('capacity');
        $ordersAfter[] = max($restaurant->orders_count - $assignedCapacity, 0);
    }

    // Check that all remaining orders are non-negative
    foreach ($ordersAfter as $after) {
        expect($after)->toBeGreaterThanOrEqual(0);
    }

    // Check that the remaining orders are roughly balanced (±10)
    $avgAfter = array_sum($ordersAfter) / count($ordersAfter);
    foreach ($ordersAfter as $after) {
        expect(abs($after - $avgAfter))->toBeLessThanOrEqual(10);
    }

    $totalRemaining = array_sum($ordersAfter);

    // Check total remaining orders and array count
    expect($totalRemaining)->toBeGreaterThan(0)
        ->and($totalRemaining)->toBeLessThan(50)
        ->and($ordersAfter)->toHaveCount(10);

});
