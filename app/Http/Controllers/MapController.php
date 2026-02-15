<?php

namespace App\Http\Controllers;

use App\Contracts\DriverDistributor;
use App\Helpers\ColorHelper;
use App\Models\Driver;
use App\Models\Restaurant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MapController extends Controller
{
    public function getMap(): View
    {
        return view('map');
    }

    public function getDivers(DriverDistributor $distributor): JsonResponse
    {
        // create randomised drivers
        Driver::truncate();
        Driver::factory()->count(100)->create();

        // distribute drivers over restaurants
        $start = microtime(true);

        $distributor->distribute();

        $durationMs = (microtime(true) - $start) * 1000;

        Log::info('Distributed', [
            'distributor' => get_class($distributor),
            'duration' => round($durationMs, 2).' ms',
        ]);

        // generate report
        $report = $distributor->generateReport();

        $drivers = Driver::all();

        return response()->json([
            'drivers' => $drivers,
            'report' => $report,
        ]);
    }

    public function getRestaurants(): JsonResponse
    {
        // update orders_count and set icon color in Restaurants


        Restaurant::query()->each(function ($restoraunt) {
            $orderCount = fake()->numberBetween(1, 50);
            $bgColor = 'green';
            if ($orderCount >= 20) {
                $bgColor = 'orange';
            } elseif ($orderCount >= 40) {
                $bgColor = 'red';
            }

            $iconColor = ColorHelper::getIconColor($bgColor);

            $restoraunt->update([
                'orders_count' => $orderCount,
                'icon_color' => $iconColor,
            ]);
        });


        $restoraunts = Restaurant::all();

        return response()->json($restoraunts);
    }
}
