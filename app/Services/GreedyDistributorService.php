<?php

namespace App\Services;

use App\Contracts\DriverDistributor;
use App\Models\Driver;
use App\Models\Restaurant;
use App\Traits\DriverOperations;
use Illuminate\Support\Facades\DB;

/**
 * GreedyDistributorService
 *
 * Distributes drivers to restaurants using a greedy algorithm that
 * balances proximity (distance) and remaining orders in restaurants.
 *
 * Each driver is assigned to the restaurant that minimizes the score:
 * score = distance * distanceWeight + balancePenalty * balanceWeight
 *
 * This service implements the DriverDistributor contract.
 */
class GreedyDistributorService implements DriverDistributor
{
    use DriverOperations;

    /**
     * Distribute drivers to restaurants.
     *
     * For each driver, the algorithm selects the restaurant with the lowest score,
     * taking into account:
     * - Distance to the restaurant (drivers prefer nearby restaurants)
     * - Balance of orders (restaurants with more remaining orders are prioritized)
     *
     * The distribution is performed in a database transaction to ensure
     * atomic updates of driver assignments.
     */
    public function distribute(): void
    {
        DB::transaction(function () {
            $restaurants = Restaurant::all()->keyBy('id');
            $drivers = Driver::all();

            $totalOrders = $restaurants->sum('orders_count');
            $totalCapacity = $drivers->sum('capacity');

            $remainingOrders = max($totalOrders - $totalCapacity, 0);
            $targetPerRestaurant = $remainingOrders / $restaurants->count();

            // Current remaining orders per restaurant
            $currentOrders = $restaurants->mapWithKeys(fn ($r) => [$r->id => $r->orders_count]);

            foreach ($drivers as $driver) {
                $bestRestaurantId = null;
                $bestScore = PHP_FLOAT_MAX;

                // Weights can be adjusted to favor distance or balance
                $distanceWeight = 1; // prioritize proximity
                $balanceWeight = 5;  // prioritize balanced load

                foreach ($restaurants as $restaurant) {

                    // Skip restaurants with no remaining orders
                    if ($currentOrders[$restaurant->id] <= 0) {
                        continue;
                    }

                    // Calculate distance between driver and restaurant
                    $distance = $this->distance(
                        $driver->current_coordinates->getLat(),
                        $driver->current_coordinates->getLng(),
                        $restaurant->coordinates->getLat(),
                        $restaurant->coordinates->getLng()
                    );

                    // Penalty for imbalance: restaurant with fewer remaining orders gets higher penalty
                    $remainingAfterAssign = $currentOrders[$restaurant->id] - $driver->capacity;
                    $balancePenalty = max($targetPerRestaurant - $remainingAfterAssign, 0);

                    // Score combines distance and balance penalty
                    $score = ($distance * $distanceWeight) + ($balancePenalty * $balanceWeight);

                    // Get the restaurant with the lowest score
                    if ($score < $bestScore) {
                        $bestScore = $score;
                        $bestRestaurantId = $restaurant->id;
                    }
                }

                if ($bestRestaurantId) {
                    $driver->restaurant_id = $bestRestaurantId;
                    $driver->save();
                    $currentOrders[$bestRestaurantId] -= $driver->capacity;
                }
            }
        });
    }
}
