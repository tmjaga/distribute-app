<?php

namespace App\Traits;

use App\Models\Driver;
use App\Models\Restaurant;

trait DriverOperations
{
    private const EARTH_RADIUS = 6371;

    /**
     * Generate a random geographic point within a specified radius from a center location
     *
     * This method creates random coordinates uniformly distributed within a circle
     * of given radius around a center point. It uses the polar coordinate method
     * with proper Earth curvature correction for longitude.
     *
     * @param  float  $lat  Center latitude in decimal degrees (-90 to 90)
     * @param  float  $lng  Center longitude in decimal degrees (-180 to 180)
     * @param  float  $radius  Maximum distance from center in kilometers (default: 5 km)
     * @return array Array containing [latitude, longitude] of the random point in decimal degrees
     */
    protected function getRandomLocation(float $lat, float $lng, float $radius = 5): array
    {
        $distance = $radius * sqrt(mt_rand() / mt_getrandmax());
        $angle = 2 * M_PI * (mt_rand() / mt_getrandmax());
        $deltaLat = $distance / self::EARTH_RADIUS * cos($angle);
        $deltaLng = $distance / (self::EARTH_RADIUS * cos(deg2rad($lat))) * sin($angle);

        return [
            $lat + rad2deg($deltaLat),
            $lng + rad2deg($deltaLng),
        ];
    }

    /**
     * Calculate the distance between two geographic coordinates using the Haversine formula
     *
     * @param  float  $lat1  Latitude of the first point in decimal degrees
     * @param  float  $lng1  Longitude of the first point in decimal degrees
     * @param  float  $lat2  Latitude of the second point in decimal degrees
     * @param  float  $lng2  Longitude of the second point in decimal degrees
     * @return float Distance between the two points in kilometers
     */
    protected function distance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lng1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) *
            pow(sin($lonDelta / 2), 2)
        ));

        return $angle * self::EARTH_RADIUS;
    }

    /**
     * Generate a report of all restaurants and drivers after distribution.
     *
     * The report includes:
     * - For each restaurant: orders before and after distribution.
     * - For each driver: current position, assigned restaurant,
     *   distance to assigned restaurant, nearest restaurant,
     *   distance to nearest restaurant.
     * - Overall average distance of all drivers to their assigned restaurants.
     *
     * @return array{
     *     restaurants: array<int, array{
     *         restaurant_id: int,
     *         title: string,
     *         orders_before: int,
     *         orders_after: int
     *     }>,
     *     drivers: array<int, array{
     *         id: int,
     *         name: string,
     *         position: array{lat: float, lng: float},
     *         assigned_restaurant: string,
     *         assigned_distance_km: float,
     *         nearest_restaurant: string,
     *         nearest_distance_km: float
     *     }>,
     *     average_distance_km: float
     * }
     */

    /*
    protected function generateReport(): array
    {
        $ordersBefore = Restaurant::pluck('orders_count', 'id')->toArray();

        $restaurants = Restaurant::with('drivers')->get();
        $drivers = Driver::with('restaurant')->orderBy('id')->get();

        $restaurantReport = [];
        $driverReport = [];

        $totalDistance = 0;
        $driverCount = 0;

        foreach ($restaurants as $restaurant) {
            $assignedCapacity = $restaurant->drivers->sum('capacity');
            $ordersAfter = max($restaurant->orders_count - $assignedCapacity, 0);

            $restaurantReport[] = [
                'restaurant_id' => $restaurant->id,
                'title' => $restaurant->title,
                'orders_before' => $ordersBefore[$restaurant->id] ?? 0,
                'orders_after' => $ordersAfter,
            ];
        }

        $allRestaurants = Restaurant::all();

        foreach ($drivers as $driver) {
            $assignedRestaurant = 'none';
            $assignedDistance = 0;

            if ($driver->restaurant) {
                $assignedDistance = $this->distance(
                    $driver->current_coordinates->getLat(),
                    $driver->current_coordinates->getLng(),
                    $driver->restaurant->coordinates->getLat(),
                    $driver->restaurant->coordinates->getLng()
                );

                $assignedRestaurant = $driver->restaurant->title;
            }

            $nearestRestaurant = null;
            $nearestDistance = PHP_FLOAT_MAX;

            foreach ($allRestaurants as $restaurant) {
                $dist = $this->distance(
                    $driver->current_coordinates->getLat(),
                    $driver->current_coordinates->getLng(),
                    $restaurant->coordinates->getLat(),
                    $restaurant->coordinates->getLng()
                );
                if ($dist < $nearestDistance) {
                    $nearestDistance = $dist;
                    $nearestRestaurant = $restaurant;
                }
            }

            $driverReport[] = [
                'id' => $driver->id,
                'name' => $driver->name,
                'position' => [
                    'lat' => $driver->current_coordinates->getLat(),
                    'lng' => $driver->current_coordinates->getLng(),
                ],
                'assigned_restaurant' => $assignedRestaurant,
                'assigned_distance_km' => round($assignedDistance, 2),
                'nearest_restaurant' => $nearestRestaurant->title,
                'nearest_distance_km' => round($nearestDistance, 2),
            ];

            $totalDistance += $assignedDistance;
            $driverCount++;
        }

        $averageDistance = $driverCount ? $totalDistance / $driverCount : 0;

        return [
            'restaurants' => $restaurantReport,
            'drivers' => $driverReport,
            'average_distance_km' => round($averageDistance, 2),
        ];
    }
    */

    public function generateReport(): array
    {
        $ordersBefore = Restaurant::pluck('orders_count', 'id')->toArray();

        $restaurants = Restaurant::with('drivers')->get();
        $drivers = Driver::with('restaurant')->orderBy('id')->get();

        $restaurantReport = [];
        $driverReport = [];

        $totalDistance = 0;
        $driverCount = 0;

        foreach ($restaurants as $restaurant) {
            $assignedCapacity = $restaurant->drivers->sum('capacity');

            $restaurantReport[] = [
                'restaurant_id' => $restaurant->id,
                'title' => $restaurant->title,
                'orders_before' => $ordersBefore[$restaurant->id] ?? 0,
                'orders_after' => max($restaurant->orders_count - $assignedCapacity, 0),
            ];
        }

        $allRestaurants = $restaurants;

        foreach ($drivers as $driver) {

            if (!$driver->current_coordinates) {
                continue;
            }

            $assignedRestaurant = 'none';
            $assignedDistance = 0;

            if ($driver->restaurant) {
                $assignedDistance = $this->distance(
                    $driver->current_coordinates->getLat(),
                    $driver->current_coordinates->getLng(),
                    $driver->restaurant->coordinates->getLat(),
                    $driver->restaurant->coordinates->getLng()
                );

                $assignedRestaurant = $driver->restaurant->title;
            }

            $nearestRestaurant = null;
            $nearestDistance = PHP_FLOAT_MAX;

            foreach ($allRestaurants as $restaurant) {
                $dist = $this->distance(
                    $driver->current_coordinates->getLat(),
                    $driver->current_coordinates->getLng(),
                    $restaurant->coordinates->getLat(),
                    $restaurant->coordinates->getLng()
                );

                if ($dist < $nearestDistance) {
                    $nearestDistance = $dist;
                    $nearestRestaurant = $restaurant;
                }
            }

            $driverReport[] = [
                'id' => $driver->id,
                'name' => $driver->name,
                'position' => [
                    'lat' => $driver->current_coordinates->getLat(),
                    'lng' => $driver->current_coordinates->getLng(),
                ],
                'assigned_restaurant' => $assignedRestaurant,
                'assigned_distance_km' => round($assignedDistance, 2),
                'nearest_restaurant' => $nearestRestaurant?->title ?? 'none',
                'nearest_distance_km' => round($nearestDistance, 2),
            ];

            $totalDistance += $assignedDistance;
            $driverCount++;
        }

        return [
            'restaurants' => $restaurantReport,
            'drivers' => $driverReport,
            'average_distance_km' => round(
                $driverCount ? $totalDistance / $driverCount : 0,
                2
            ),
        ];
    }

}
