<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ElevationProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Consulta elevación del terreno vía Open Topo Data (SRTM 30 m) con caché.
 */
final class ElevationService implements ElevationProvider
{
    private const DATASET = 'srtm30m';

    private const CACHE_TTL_HOURS = 24;

    /**
     * @param  string[]  $rawPoints
     * @return array<int, array{elevation: float|null, lat: float, lng: float}>
     */
    public function fetchElevations(array $rawPoints): array
    {
        $cacheHits = [];
        $pointsToFetch = [];
        $indexMap = [];

        foreach ($rawPoints as $idx => $rawPoint) {
            $cacheKey = $this->buildCacheKey($rawPoint);
            $cached = Cache::get($cacheKey);

            if ($cached !== null) {
                $cacheHits[$idx] = $cached;
            } else {
                $indexMap[count($pointsToFetch)] = $idx;
                $pointsToFetch[] = $rawPoint;
            }
        }

        $freshResults = [];
        if ($pointsToFetch !== []) {
            $freshResults = $this->callOpenTopoData($pointsToFetch);

            foreach ($freshResults as $fetchIdx => $result) {
                $rawIdx = $indexMap[$fetchIdx] ?? $fetchIdx;
                $rawPoint = $rawPoints[$rawIdx] ?? $pointsToFetch[$fetchIdx];
                Cache::put($this->buildCacheKey($rawPoint), $result, now()->addHours(self::CACHE_TTL_HOURS));
            }
        }

        $results = [];
        $freshIdx = 0;
        foreach (array_keys($rawPoints) as $idx) {
            if (isset($cacheHits[$idx])) {
                $results[] = $cacheHits[$idx];
            } else {
                $results[] = $freshResults[$freshIdx] ?? $this->parsePoint($rawPoints[$idx], null);
                $freshIdx++;
            }
        }

        return $results;
    }

    /**
     * @param  string[]  $rawPoints
     * @return array<int, array{elevation: float|null, lat: float, lng: float}>
     */
    private function callOpenTopoData(array $rawPoints): array
    {
        $url = 'https://api.opentopodata.org/v1/' . self::DATASET;

        $response = Http::timeout(10)
            ->retry(2, 500)
            ->get($url, ['locations' => implode('|', $rawPoints)]);

        if (!$response->successful()) {
            Log::warning('ElevationService: Open Topo Data respondió con error.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return array_map(fn ($p) => $this->parsePoint($p, null), $rawPoints);
        }

        $apiResults = $response->json('results', []);
        $parsed = [];

        foreach ($rawPoints as $idx => $rawPoint) {
            $apiResult = $apiResults[$idx] ?? null;
            $elevation = is_array($apiResult) ? ($apiResult['elevation'] ?? null) : null;
            $parsed[] = $this->parsePoint($rawPoint, $elevation !== null ? (float) $elevation : null);
        }

        return $parsed;
    }

    /**
     * @return array{elevation: float|null, lat: float, lng: float}
     */
    private function parsePoint(string $rawPoint, ?float $elevation): array
    {
        $parts = explode(',', $rawPoint, 2);
        $lat = isset($parts[0]) ? (float) trim($parts[0]) : 0.0;
        $lng = isset($parts[1]) ? (float) trim($parts[1]) : 0.0;

        return ['elevation' => $elevation, 'lat' => $lat, 'lng' => $lng];
    }

    private function buildCacheKey(string $rawPoint): string
    {
        $parts = explode(',', $rawPoint, 2);
        $lat = isset($parts[0]) ? round((float) trim($parts[0]), 4) : 0.0;
        $lng = isset($parts[1]) ? round((float) trim($parts[1]), 4) : 0.0;

        return "elevation_srtm30m_{$lat}_{$lng}";
    }
}
