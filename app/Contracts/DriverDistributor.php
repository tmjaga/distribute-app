<?php

namespace App\Contracts;

interface DriverDistributor
{
    public function distribute(): void;
}
