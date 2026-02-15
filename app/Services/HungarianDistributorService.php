<?php

namespace App\Services;

use App\Contracts\DriverDistributor;
use App\Models\Driver;
use App\Models\Restaurant;
use App\Traits\DriverOperations;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * HungarianDistributorService
 *
 * Distributes drivers to restaurants based a Hungarian-inspired greedy algorithm
 * that balances distance and restaurant load.
 *
 * This service implements the DriverDistributor contract.
 * It calculates a cost matrix of distances and assigns drivers to restaurants
 * while attempting to keep restaurant loads balanced.
 */
class HungarianDistributorService implements DriverDistributor
{
    use DriverOperations;

    /**
     * Distribute drivers to restaurants.
     *
     * - Only unassigned drivers are considered.
     * - The target number of orders per restaurant is computed.
     * - Drivers are assigned based on a score combining distance and load balance.
     * - The assignment is wrapped in a database transaction for atomic updates.
     */
    public function distribute(): void
    {
        $restaurants = Restaurant::all();
        $drivers = Driver::whereNull('restaurant_id')->get();

        if ($drivers->isEmpty() || $restaurants->isEmpty()) {
            return;
        }

        $totalOrders = $restaurants->sum('orders_count');
        $totalCapacity = $drivers->sum('capacity');
        $remainingOrders = max($totalOrders - $totalCapacity, 0);
        $targetRemaining = $remainingOrders / $restaurants->count();

        // Build the distance/cost matrix
        $costMatrix = $this->buildCostMatrix($drivers, $restaurants);

        // Perform a balanced assignment based on distance and load
        $assignments = $this->balancedAssignment($costMatrix, $drivers, $restaurants, $targetRemaining);

        DB::transaction(function () use ($assignments) {
            foreach ($assignments as $driverId => $restaurantId) {
                Driver::where('id', $driverId)->update([
                    'restaurant_id' => $restaurantId,
                ]);
            }
        });
    }

    /**
     * Build a cost matrix of distances between drivers and restaurants.
     *
     * @param  Collection<int, Driver>  $drivers
     * @param  Collection<int, Restaurant>  $restaurants
     * @return array<int, array<int, float>> Distance matrix [driverIndex][restaurantIndex]
     */
    private function buildCostMatrix(Collection $drivers, Collection $restaurants): array
    {
        $matrix = [];

        foreach ($drivers as $driverIndex => $driver) {
            foreach ($restaurants as $restaurantIndex => $restaurant) {
                $distance = $this->distance(
                    $driver->current_coordinates->getLat(),
                    $driver->current_coordinates->getLng(),
                    $restaurant->coordinates->getLat(),
                    $restaurant->coordinates->getLng()
                );

                $matrix[$driverIndex][$restaurantIndex] = $distance;
            }
        }

        return $matrix;
    }

    /**
     * Perform a balanced assignment of drivers to restaurants.
     *
     * The score for each driver-restaurant pair considers:
     * - Distance to the restaurant
     * - Current restaurant load vs. target load
     * - Deviation from target restaurant capacity
     *
     * @param  array<int, array<int, float>>  $costMatrix
     * @param  Collection<int, Driver>  $drivers
     * @param  Collection<int, Restaurant>  $restaurants
     * @return array<int, int> Mapping [driverId => restaurantId]
     */
    private function balancedAssignment(
        array $costMatrix,
        Collection $drivers,
        Collection $restaurants,
        float $targetRemaining): array
    {
        $assignments = [];
        $restaurantLoad = array_fill(0, $restaurants->count(), 0);

        // Build a list of drivers with their index, capacity, and costs
        $driverList = [];
        foreach ($drivers as $index => $driver) {
            $driverList[] = [
                'index' => $index,
                'capacity' => $driver->capacity,
                'costs' => $costMatrix[$index],
            ];
        }

        // Sort drivers by their minimal distance to any restaurant
        // Drivers with fewer close options are assigned first
        usort($driverList, function ($a, $b) {
            return min($a['costs']) <=> min($b['costs']);
        });

        foreach ($driverList as $driverInfo) {
            $driverIndex = $driverInfo['index'];
            $capacity = $driverInfo['capacity'];
            $distances = $driverInfo['costs'];

            // Get restaurant indices sorted by distance
            $restaurantIndices = range(0, $restaurants->count() - 1);
            usort($restaurantIndices, function ($a, $b) use ($distances) {
                return $distances[$a] <=> $distances[$b];
            });

            $selectedRestaurant = null;
            $minScore = PHP_FLOAT_MAX;

            $distanceWeight = 0.7;  // weight for proximity
            $balanceWeight = 10;    // weight for load balance

            foreach ($restaurantIndices as $restaurantIndex) {
                $restaurant = $restaurants[$restaurantIndex];
                $currentLoad = $restaurantLoad[$restaurantIndex];

                if ($currentLoad >= $restaurant->orders_count) {
                    continue;
                }

                $distance = $distances[$restaurantIndex];

                $remainingAfterAssign = $restaurant->orders_count - ($currentLoad + $capacity);
                $balancePenalty = pow(max($targetRemaining - $remainingAfterAssign, 0), 1.3);

                // Compute score combining distance and balance
                $score = ($distance * $distanceWeight) + ($balancePenalty * $balanceWeight);

                if ($score < $minScore) {
                    $minScore = $score;
                    $selectedRestaurant = $restaurantIndex;
                }
            }

            if ($selectedRestaurant !== null) {
                $assignments[$drivers[$driverIndex]->id] = $restaurants[$selectedRestaurant]->id;
                $restaurantLoad[$selectedRestaurant] += $capacity;
            }
        }

        return $assignments;
    }
}
