<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TarfinLabs\LaravelSpatial\Types\Point;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Restaurant>
 */
class RestaurantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $lat = $this->faker->randomFloat(6, 42.60, 42.75);
        $lng = $this->faker->randomFloat(6, 23.20, 23.45);

        return [
            'title' => $this->faker->company(),
            'coordinates' => new Point($lat, $lng),
            'orders_count' => $this->faker->numberBetween(1, 50),
            'icon_color' => $this->faker->hexColor(),
        ];
    }
}
