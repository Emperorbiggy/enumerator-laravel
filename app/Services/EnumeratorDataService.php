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
        $startTime = microtime(true);
        
        return Cache::remember('lgas', self::CACHE_TTL, function () use ($startTime) {
            try {
                $baseUrl = Config::get('services.pu_api.url');
                $url = "{$baseUrl}/api/lgas";
                
                Log::info('DataService: Fetching LGAs from external API', [
                    'url' => $url,
                    'cache_hit' => false,
                    'timestamp' => now()->toISOString()
                ]);
                
                $response = Http::timeout(30)->get($url);
                
                if ($response->successful()) {
                    $data = $response->json();
                    $responseTime = round((microtime(true) - $startTime) * 1000, 2);
                    
                    Log::info('DataService: Successfully fetched LGAs', [
                        'count' => is_array($data) ? count($data) : 0,
                        'response_time_ms' => $responseTime,
                        'status_code' => $response->status(),
                        'timestamp' => now()->toISOString()
                    ]);
                    return $data;
                } else {
                    $responseTime = round((microtime(true) - $startTime) * 1000, 2);
                    
                    Log::error('DataService: Failed to fetch LGAs', [
                        'status' => $response->status(),
                        'response_time_ms' => $responseTime,
                        'response_body' => $response->body(),
                        'timestamp' => now()->toISOString()
                    ]);
                    return [];
                }
            } catch (\Exception $e) {
                $responseTime = round((microtime(true) - $startTime) * 1000, 2);
                
                Log::error('DataService: Exception while fetching LGAs', [
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'response_time_ms' => $responseTime,
                    'timestamp' => now()->toISOString()
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
        $startTime = microtime(true);
        
        return Cache::remember("wards_{$lgaName}", self::CACHE_TTL, function () use ($lgaName, $startTime) {
            try {
                // First get the LGA ID by name
                $lgaId = $this->getLGAIdByName($lgaName);
                
                if (!$lgaId) {
                    Log::warning('DataService: LGA not found', [
                        'lga_name' => $lgaName,
                        'timestamp' => now()->toISOString()
                    ]);
                    return [];
                }

                $baseUrl = Config::get('services.pu_api.url');
                $url = "{$baseUrl}/api/wards-by-lga/{$lgaId}";
                
                Log::info('DataService: Fetching wards from external API', [
                    'url' => $url,
                    'lga_id' => $lgaId,
                    'lga_name' => $lgaName,
                    'cache_hit' => false,
                    'timestamp' => now()->toISOString()
                ]);
                
                $response = Http::timeout(30)->get($url);
                
                if ($response->successful()) {
                    $data = $response->json();
                    $responseTime = round((microtime(true) - $startTime) * 1000, 2);
                    
                    Log::info('DataService: Successfully fetched wards', [
                        'lga_name' => $lgaName,
                        'lga_id' => $lgaId,
                        'count' => is_array($data) ? count($data) : 0,
                        'response_time_ms' => $responseTime,
                        'status_code' => $response->status(),
                        'timestamp' => now()->toISOString()
                    ]);
                    return $data;
                } else {
                    $responseTime = round((microtime(true) - $startTime) * 1000, 2);
                    
                    Log::error('DataService: Failed to fetch wards', [
                        'lga_name' => $lgaName,
                        'lga_id' => $lgaId,
                        'status' => $response->status(),
                        'response_time_ms' => $responseTime,
                        'response_body' => $response->body(),
                        'timestamp' => now()->toISOString()
                    ]);
                    return [];
                }
            } catch (\Exception $e) {
                $responseTime = round((microtime(true) - $startTime) * 1000, 2);
                
                Log::error('DataService: Exception while fetching wards', [
                    'lga_name' => $lgaName,
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'response_time_ms' => $responseTime,
                    'timestamp' => now()->toISOString()
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
        $startTime = microtime(true);
        
        return Cache::remember("polling_units_{$wardName}", self::CACHE_TTL, function () use ($wardName, $startTime) {
            try {
                // First get the ward ID by name
                $wardId = $this->getWardIdByName($wardName);
                
                if (!$wardId) {
                    Log::warning('DataService: Ward not found', [
                        'ward_name' => $wardName,
                        'timestamp' => now()->toISOString()
                    ]);
                    return [];
                }

                $baseUrl = Config::get('services.pu_api.url');
                $url = "{$baseUrl}/api/polling-units-by-ward/{$wardId}";
                
                Log::info('DataService: Fetching polling units from external API', [
                    'url' => $url,
                    'ward_id' => $wardId,
                    'ward_name' => $wardName,
                    'cache_hit' => false,
                    'timestamp' => now()->toISOString()
                ]);
                
                $response = Http::timeout(30)->get($url);
                
                if ($response->successful()) {
                    $data = $response->json();
                    $responseTime = round((microtime(true) - $startTime) * 1000, 2);
                    
                    Log::info('DataService: Successfully fetched polling units', [
                        'ward_name' => $wardName,
                        'ward_id' => $wardId,
                        'count' => is_array($data) ? count($data) : 0,
                        'response_time_ms' => $responseTime,
                        'status_code' => $response->status(),
                        'timestamp' => now()->toISOString()
                    ]);
                    return $data;
                } else {
                    $responseTime = round((microtime(true) - $startTime) * 1000, 2);
                    
                    Log::error('DataService: Failed to fetch polling units', [
                        'ward_name' => $wardName,
                        'ward_id' => $wardId,
                        'status' => $response->status(),
                        'response_time_ms' => $responseTime,
                        'response_body' => $response->body(),
                        'timestamp' => now()->toISOString()
                    ]);
                    return [];
                }
            } catch (\Exception $e) {
                $responseTime = round((microtime(true) - $startTime) * 1000, 2);
                
                Log::error('DataService: Exception while fetching polling units', [
                    'ward_name' => $wardName,
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'response_time_ms' => $responseTime,
                    'timestamp' => now()->toISOString()
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
