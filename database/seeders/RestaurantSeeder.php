<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use TarfinLabs\LaravelSpatial\Types\Point;

class RestaurantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $restaurantsData = File::get(database_path('seeders/data/restaurants.json'));
        $restaurants = json_decode($restaurantsData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error('Invalid JSON format: '.json_last_error_msg());

            return;
        }

        $added = 0;

        foreach ($restaurants as $restaurant) {
            Restaurant::create([
                'title' => $restaurant['title'],
                'coordinates' => new Point($restaurant['coordinates'][0], $restaurant['coordinates'][1]),
                'orders_count' => fake()->numberBetween(5, 50),
            ]);
            $added++;
        }

        $this->command->info($added.' Restaurants added!');
    }
}
