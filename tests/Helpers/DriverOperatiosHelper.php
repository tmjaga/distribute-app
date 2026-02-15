<?php

namespace Tests\Helpers;

use App\Traits\DriverOperations;

class DriverOperatiosHelper
{
    use DriverOperations;

    public function computeDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        return $this->distance($lat1, $lng1, $lat2, $lng2);
    }

    public function callGenerateReport(): array
    {
        return $this->generateReport();
    }
}
