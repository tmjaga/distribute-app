<?php

use App\Services\DriverLocationService;

use function Tests\Helpers\runMapLocationContractTests;

beforeEach(function () {
    $this->service = app(DriverLocationService::class);
});

runMapLocationContractTests(new DriverLocationService);
