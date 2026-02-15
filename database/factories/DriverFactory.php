<?php

namespace Database\Factories;

use App\Contracts\MapLocation;
use App\Models\Restaurant;
use App\Services\DriverLocationService;
use Illuminate\Database\Eloquent\Factories\Factory;
use TarfinLabs\LaravelSpatial\Types\Point;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Driver>
 */
class DriverFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $locationService = app(MapLocation::class);

        $restaurant = Restaurant::inRandomOrder()->first();

        $lat = $restaurant->coordinates->getLat();
        $lng = $restaurant->coordinates->getLng();

        [$driverLat, $driverLng] = $locationService->randomMapLocation($lat, $lng);

        return [
            'name' => sprintf('%s %s', $this->faker->firstName(), $this->faker->lastName()),
            'restaurant_id' => null,
            'current_coordinates' => new Point($driverLat, $driverLng),
            'capacity' => fake()->numberBetween(1, 4),
        ];
    }
}
