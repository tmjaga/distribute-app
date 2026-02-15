<?php

use App\Models\Driver;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use TarfinLabs\LaravelSpatial\Types\Point;
use Tests\Helpers\DriverOperatiosHelper;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->trait = new DriverOperatiosHelper;
});

it('generateReport works with two restaurants and drivers', function () {

    $restaurants = Restaurant::factory()->count(2)
        ->sequence(
            ['coordinates' => new Point(42.6977, 23.3219), 'orders_count' => 50],
            ['coordinates' => new Point(42.7000, 23.3250), 'orders_count' => 45],
        )->create();

    Driver::factory()->count(2)
        ->sequence(
            [
                'current_coordinates' => new Point(42.6980, 23.3220),
                'restaurant_id' => $restaurants[0]->id,
                'capacity' => 2,
            ],
            [
                'current_coordinates' => new Point(42.6995, 23.3245),
                'restaurant_id' => $restaurants[1]->id,
                'capacity' => 3,
            ],
        )->create();

    $report = $this->trait->generateReport();


    expect($report)->toHaveKeys([
        'restaurants',
        'drivers',
        'average_distance_km',
    ])->and($report['restaurants'])->toHaveCount(2)->and($report['drivers'])->toHaveCount(2);


    foreach ($report['restaurants'] as $restaurantReport) {
        expect($restaurantReport)->toHaveKeys([
            'restaurant_id',
            'title',
            'orders_before',
            'orders_after',
        ]);
    }

    foreach ($report['drivers'] as $driverReport) {
        expect($driverReport)->toHaveKeys([
            'id',
            'name',
            'position',
            'assigned_restaurant',
            'assigned_distance_km',
            'nearest_restaurant',
            'nearest_distance_km',
        ])->and($driverReport['position'])->toHaveKeys(['lat', 'lng']);

    }

    expect($report['average_distance_km'])->toBeFloat();
});
