<?php

use App\Services\DriverOsrmLocationService;
use Illuminate\Support\Facades\Http;
use function Tests\Helpers\runMapLocationContractTests;

beforeEach(function () {
    Http::fake([
        '*' => Http::response([
            'waypoints' => [
                ['location' => [23.3219, 42.6977]],
            ],
        ]),
    ]);
});

runMapLocationContractTests(new DriverOsrmLocationService);
