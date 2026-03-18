<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class EnumeratorDataService
{
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get all LGAs from external API
     */
    public function getLGAs(): array
    {
        return Cache::remember('lgas', self::CACHE_TTL, function () {
            try {
                $baseUrl = Config::get('services.pu_api.url');
                $url = "{$baseUrl}/api/lgas";
                
                Log::info('Fetching LGAs from external API', ['url' => $url]);
                
                $response = Http::timeout(30)->get($url);
                
                if ($response->successful()) {
                    $data = $response->json();
                    Log::info('Successfully fetched LGAs', ['count' => count($data)]);
                    return $data;
                } else {
                    Log::error('Failed to fetch LGAs', [
                        'status' => $response->status(),
                        'response' => $response->body()
                    ]);
                    return [];
                }
            } catch (\Exception $e) {
                Log::error('Exception while fetching LGAs', [
                    'error' => $e->getMessage()
                ]);
                return [];
            }
        });
    }

    /**
     * Get wards for a specific LGA from external API
     */
    public function getWardsByLGA(string $lgaName): array
    {
        return Cache::remember("wards_{$lgaName}", self::CACHE_TTL, function () use ($lgaName) {
            try {
                // First get the LGA ID by name
                $lgaId = $this->getLGAIdByName($lgaName);
                
                if (!$lgaId) {
                    Log::warning('LGA not found', ['lga_name' => $lgaName]);
                    return [];
                }

                $baseUrl = Config::get('services.pu_api.url');
                $url = "{$baseUrl}/api/wards-by-lga/{$lgaId}";
                
                Log::info('Fetching wards from external API', [
                    'url' => $url,
                    'lga_id' => $lgaId,
                    'lga_name' => $lgaName
                ]);
                
                $response = Http::timeout(30)->get($url);
                
                if ($response->successful()) {
                    $data = $response->json();
                    Log::info('Successfully fetched wards', [
                        'lga_name' => $lgaName,
                        'count' => count($data)
                    ]);
                    return $data;
                } else {
                    Log::error('Failed to fetch wards', [
                        'lga_name' => $lgaName,
                        'lga_id' => $lgaId,
                        'status' => $response->status(),
                        'response' => $response->body()
                    ]);
                    return [];
                }
            } catch (\Exception $e) {
                Log::error('Exception while fetching wards', [
                    'lga_name' => $lgaName,
                    'error' => $e->getMessage()
                ]);
                return [];
            }
        });
    }

    /**
     * Get polling units for a specific ward from external API
     */
    public function getPollingUnitsByWard(string $wardName): array
    {
        return Cache::remember("polling_units_{$wardName}", self::CACHE_TTL, function () use ($wardName) {
            try {
                // First get the ward ID by name
                $wardId = $this->getWardIdByName($wardName);
                
                if (!$wardId) {
                    Log::warning('Ward not found', ['ward_name' => $wardName]);
                    return [];
                }

                $baseUrl = Config::get('services.pu_api.url');
                $url = "{$baseUrl}/api/polling-units-by-ward/{$wardId}";
                
                Log::info('Fetching polling units from external API', [
                    'url' => $url,
                    'ward_id' => $wardId,
                    'ward_name' => $wardName
                ]);
                
                $response = Http::timeout(30)->get($url);
                
                if ($response->successful()) {
                    $data = $response->json();
                    Log::info('Successfully fetched polling units', [
                        'ward_name' => $wardName,
                        'count' => count($data)
                    ]);
                    return $data;
                } else {
                    Log::error('Failed to fetch polling units', [
                        'ward_name' => $wardName,
                        'ward_id' => $wardId,
                        'status' => $response->status(),
                        'response' => $response->body()
                    ]);
                    return [];
                }
            } catch (\Exception $e) {
                Log::error('Exception while fetching polling units', [
                    'ward_name' => $wardName,
                    'error' => $e->getMessage()
                ]);
                return [];
            }
        });
    }

    /**
     * Get LGA ID by name
     */
    private function getLGAIdByName(string $lgaName): ?int
    {
        $lgas = $this->getLGAs();
        
        foreach ($lgas as $lga) {
            // Case-insensitive comparison
            if (strcasecmp($lga['name'], $lgaName) === 0) {
                return $lga['id'];
            }
        }
        
        return null;
    }

    /**
     * Get ward ID by name
     */
    private function getWardIdByName(string $wardName): ?int
    {
        // This is a bit tricky since we don't have all wards cached
        // For now, we'll need to search through all LGAs to find the ward
        $lgas = $this->getLGAs();
        
        foreach ($lgas as $lga) {
            $wards = $this->getWardsByLGA($lga['name']);
            
            foreach ($wards as $ward) {
                // Case-insensitive comparison
                if (strcasecmp($ward['name'], $wardName) === 0) {
                    return $ward['id'];
                }
            }
        }
        
        return null;
    }
}
