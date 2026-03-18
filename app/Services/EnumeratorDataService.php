<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class EnumeratorDataService
{
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get all LGAs from external API
     */
    public function getLGAs(): array
    {
        return Cache::remember('lgas', self::CACHE_TTL, function () {
            // For now, return hardcoded data - replace with actual API call
            return [
                ['id' => 1, 'name' => 'Atakumosa East'],
                ['id' => 2, 'name' => 'Atakumosa West'],
                ['id' => 3, 'name' => 'Ayedaade'],
                ['id' => 4, 'name' => 'Ayedire'],
                ['id' => 5, 'name' => 'Boluwaduro'],
                ['id' => 6, 'name' => 'Boripe'],
                ['id' => 7, 'name' => 'Ede North'],
                ['id' => 8, 'name' => 'Ede South'],
                ['id' => 9, 'name' => 'Egbedore'],
                ['id' => 10, 'name' => 'Ejigbo'],
                ['id' => 11, 'name' => 'Ife Central'],
                ['id' => 12, 'name' => 'Ife East'],
                ['id' => 13, 'name' => 'Ife North'],
                ['id' => 14, 'name' => 'Ife South'],
                ['id' => 15, 'name' => 'Ifedayo'],
                ['id' => 16, 'name' => 'Ifelodun'],
                ['id' => 17, 'name' => 'Ila'],
                ['id' => 18, 'name' => 'Ilesa East'],
                ['id' => 19, 'name' => 'Ilesa West'],
                ['id' => 20, 'name' => 'Irepodun'],
                ['id' => 21, 'name' => 'Irewole'],
                ['id' => 22, 'name' => 'Isokan'],
                ['id' => 23, 'name' => 'Iwo'],
                ['id' => 24, 'name' => 'Obokun'],
                ['id' => 25, 'name' => 'Odo Otin'],
                ['id' => 26, 'name' => 'Ola Oluwa'],
                ['id' => 27, 'name' => 'Olorunda'],
                ['id' => 28, 'name' => 'Oriade'],
                ['id' => 29, 'name' => 'Orolu'],
                ['id' => 30, 'name' => 'Osogbo'],
            ];

            // Example of actual API call:
            // $response = Http::get('https://api.example.com/lgas');
            // return $response->json();
        });
    }

    /**
     * Get wards for a specific LGA from external API
     */
    public function getWardsByLGA(string $lgaName): array
    {
        return Cache::remember("wards_{$lgaName}", self::CACHE_TTL, function () use ($lgaName) {
            $wardData = [
                'Atakumosa East' => [
                    ['id' => 1, 'name' => 'Ward 1: Osu'],
                    ['id' => 2, 'name' => 'Ward 2: Ifewara'],
                    ['id' => 3, 'name' => 'Ward 3: Oke-Bode'],
                    ['id' => 4, 'name' => 'Ward 4: Oke-Ila'],
                    ['id' => 5, 'name' => 'Ward 5: Ijabe'],
                    ['id' => 6, 'name' => 'Ward 6: Idominasi'],
                    ['id' => 7, 'name' => 'Ward 7: Ikeji-Ile'],
                    ['id' => 8, 'name' => 'Ward 8: Ikeji-Arakeji'],
                    ['id' => 9, 'name' => 'Ward 9: Oke-Ila Orangun'],
                    ['id' => 10, 'name' => 'Ward 10: Ipetu-Ile'],
                ],
                'Atakumosa West' => [
                    ['id' => 11, 'name' => 'Ward 1: Ibodi'],
                    ['id' => 12, 'name' => 'Ward 2: Ifelodun'],
                    ['id' => 13, 'name' => 'Ward 3: Iba'],
                    ['id' => 14, 'name' => 'Ward 4: Oke-Omi'],
                    ['id' => 15, 'name' => 'Ward 5: Odo-Owa'],
                    ['id' => 16, 'name' => 'Ward 6: Isare'],
                    ['id' => 17, 'name' => 'Ward 7: Iperindo'],
                    ['id' => 18, 'name' => 'Ward 8: Ajebandele'],
                    ['id' => 19, 'name' => 'Ward 9: Owa-kajola'],
                    ['id' => 20, 'name' => 'Ward 10: Oke-Irun'],
                ],
                // Add more LGAs as needed...
            ];

            return $wardData[$lgaName] ?? [];

            // Example of actual API call:
            // $response = Http::get("https://api.example.com/lgas/{$lgaId}/wards");
            // return $response->json();
        });
    }

    /**
     * Get polling units for a specific ward from external API
     */
    public function getPollingUnitsByWard(string $wardName): array
    {
        return Cache::remember("polling_units_{$wardName}", self::CACHE_TTL, function () use ($wardName) {
            $pollingUnitData = [
                'Ward 1: Osu' => [
                    ['id' => 1, 'name' => 'PU 001: Osu Central School'],
                    ['id' => 2, 'name' => 'PU 002: Osu Market Square'],
                    ['id' => 3, 'name' => 'PU 003: Osu Primary Health Center'],
                    ['id' => 4, 'name' => 'PU 004: Oluode\'s Palace'],
                    ['id' => 5, 'name' => 'PU 005: Osu Grammar School'],
                ],
                'Ward 2: Ifewara' => [
                    ['id' => 6, 'name' => 'PU 001: Ifewara Town Hall'],
                    ['id' => 7, 'name' => 'PU 002: Ifewara Central Mosque'],
                    ['id' => 8, 'name' => 'PU 003: Ifewara Primary School'],
                    ['id' => 9, 'name' => 'PU 004: Oke-Oja Ifewara'],
                    ['id' => 10, 'name' => 'PU 005: Ifewara Market'],
                ],
                // Add more wards as needed...
            ];

            return $pollingUnitData[$wardName] ?? [];

            // Example of actual API call:
            // $response = Http::get("https://api.example.com/wards/{$wardId}/polling-units");
            // return $response->json();
        });
    }

    /**
     * Get default polling units for wards not specifically defined
     */
    public function getDefaultPollingUnits(string $wardName): array
    {
        return [
            ['id' => 999, 'name' => "PU 001: {$wardName} Central School"],
            ['id' => 1000, 'name' => "PU 002: {$wardName} Market Square"],
            ['id' => 1001, 'name' => "PU 003: {$wardName} Health Center"],
            ['id' => 1002, 'name' => "PU 004: {$wardName} Town Hall"],
            ['id' => 1003, 'name' => "PU 005: {$wardName} Grammar School"],
            ['id' => 1004, 'name' => "PU 006: {$wardName} Primary School"],
            ['id' => 1005, 'name' => "PU 007: {$wardName} Community Center"],
            ['id' => 1006, 'name' => "PU 008: {$wardName} Motor Park"],
            ['id' => 1007, 'name' => "PU 009: {$wardName} Local Government Secretariat"],
            ['id' => 1008, 'name' => "PU 010: {$wardName} Palace Ground"],
        ];
    }
}
