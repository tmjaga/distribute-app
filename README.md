# Optinal Drivers Distribution to Restaurants

## Table of Contents

1. [Overview](#overview)
2. [Requirements](#requirements)
3. [Installation](#installation)
4. [Run Tests](#running-tests)
5. [Used Packages](#used-packages)

## Overview
The goal of this project is to simulate driver distribution and optimize their assignment to restaurants in a balanced and distance-efficient way.

## 1. Randomization Phase

During this phase, the system initializes randomized data for drivers and restaurants following these rules:

### Drivers

- Each driver receives a random location within a maximum distance of **5 km** from any restaurant.
    - The restaurant reference and the exact distance must be randomly selected.
- Each driver receives a **capacity value between 1 and 4**, representing how many orders they can carry.
- Each driver’s `next_location` is reset to `null`.

Driver random location relative to a randomly selected restaurant is implemented using three different approaches:
1. Random coordinate selection within a specified radius from a given point
   - **+** Fastest method
   - **-** Driver may be positioned outside of a road (e.g., inside buildings or parks)
   - **Implemented:** `App\Services\DriverLocationService.php`
   This is the fastest method, but the driver may be positioned outside of a road (e.g., inside buildings or parks).

2. Random coordinate selection within a specified radius from a given point with correction using OSRM API
   - **+** Adjusts coordinates based on routing
   - **-** Slowest method due to an API request for each call; does not consider road type and may place drivers on pedestrian-only roads
   - **Implemented:** `DriverOsrmLocationService.php`

3. Coordinate selection using a preprocessed and preloaded Polyline road dataset (Map road segments data) JSON file `(/database/seeders/data/sofia_roads.json)` loaded in Redis 
   - **+** Most accurate positioning method
   - **-** Requires additional system resources
   - **Implemented:** `DriverRoadsLocationService.php`

Implemented in the class:
App\Services\DriverLocationService

### Restaurants

- Each restaurant receives a randomly generated **orders count between 5 and 50**.

## 2. Optimization (Solve Phase)

After randomization, the system must assign drivers to restaurants in a way that is both **balanced** and **distance-efficient**.

### Objectives

#### Balanced Order Distribution
- The number of unassigned orders across restaurants should be relatively equal after driver assignment.

#### Distance Optimization
- The total travel distance of all drivers should be minimized.
- Restaurants closer to a driver should generally have higher priority as assignment targets.

Balanced Order Distribution and Distance Optimization are implemented using two approaches:
1. Based on Greedy Balanced Assignment Algorithm – implemented in class `App\Services\GreedyDistributorService.php`
2. Based on Hungarian Algorithm – implemented in the class: `App\Services\HungarianDistributorService.php`
   
Both methods work approximately the same, but the Hungarian Algorithm is slightly slower.

You can select the driver location method and the distribution method as follows:
In the file `App\Providers\AppServiceProvider.php`, inside the `register()` method, there are the following lines:
```php
// Driver location services
// $this->app->singleton(MapLocation::class, DriverLocationService::class);
// $this->app->singleton(MapLocation::class, DriverOsrmLocationService::class);
$this->app->singleton(MapLocation::class, DriverRoadsLocationService::class);

// Distributor services
$this->app->singleton(DriverDistributor::class, GreedyDistributorService::class);
// $this->app->singleton(DriverDistributor::class, HungarianDistributorService::class);
```
Uncomment the desired line for driver positioning method and distribution method. The other lines should remain commented out.

## Requirements

-   Docker

    > If you are using Windows, please make sure to install Docker Desktop.
    > Next, you should ensure that Windows Subsystem for Linux 2 (WSL2) is installed and enabled.

-   [Postman](https://www.postman.com) or other HTTP client for testing the API.
-   [Composer](https://getcomposer.org/)

## Installation

### 1. Clone the project

```bash
git clone https://github.com/tmjaga/distribute-app.git
```

### 2. Navigate into the project folder using terminal

```bash
cd distribute-app
```
### 3. Build the Docker images and start all containers

```bash
docker compose up -d --build
```

### 4. Enter in to the distribute-app container

```bash
docker exec -it distribute-app bash
```

### 5. Inside distribute-app the container run the following commands:
```bash
composer install       # Install PHP dependencies
npm install            # Install Node.js dependencies
npm run build           # Compile and build frontend assets
cp .env.example .env    # Copy example environment file to .env
php artisan key:generate # Generate application encryption key
php artisan migrate --seed # Run database migrations and seed initial data
php artisan roads:preload  # Preload road from Polyline road dataset JSON file into the Redis
```

### 6. The application will be accessible at: http://localhost:8000
On the start page, all restaurants will be displayed on the map.
Click the "Place Drivers" button several times (wait for the data to load from the server.) to place drivers on the map and assign them to restaurants according to the specified conditions.
A report with data about restaurants and drivers also will be provided.

## Running Tests

Inside distribute-app the container run

```bash
php artisan test
```

## Used Packages

- [Laravel 12](https://laravel.com)
- [Laravel Spatial](https://github.com/tarfin-labs/laravel-spatial) - laravel package to work with geospatial data types and functions.
- [JSON Machine](https://github.com/halaxa/json-machine) - is an efficient, easy-to-use and fast JSON stream/pull/incremental/lazy (whatever you name it) parser
- [msgpack.php](https://github.com/rybakit/msgpack.php) - a pure PHP implementation of the MessagePack serialization format.
- [Leaflet](https://leafletjs.com) - open-source JavaScript library for mobile-friendly interactive maps.
- [Leaflet.awesome-markers plugin](https://github.com/lennardv2/Leaflet.awesome-markers) - colorful iconic & retina-proof markers for Leaflet, based on the Glyphicons / Font-Awesome icons.


