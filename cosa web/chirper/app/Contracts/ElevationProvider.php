<?php

declare(strict_types=1);

namespace App\Contracts;

interface ElevationProvider
{
    /**
     * @param  string[]  $rawPoints
     * @return array<int, array{elevation: float|null, lat: float, lng: float}>
     */
    public function fetchElevations(array $rawPoints): array;
}
