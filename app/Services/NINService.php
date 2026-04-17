<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NINService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = 'https://api.prembly.com';
        $this->apiKey = config('services.prembly.api_key') ?? 'test_sk_0a3c0fddd722474a9001b7a1d7123d25';
    }

    /**
     * Verify NIN using Prembly API
     *
     * @param string $nin
     * @return array
     * @throws \Exception
     */
    public function verifyNIN(string $nin): array
    {
        try {
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'x-api-key' => $this->apiKey,
            ])->post("{$this->baseUrl}/verification/vnin-basic", [
                'number' => $nin,
            ]);

            if (!$response->successful()) {
                Log::error('NIN verification failed', [
                    'nin' => $nin,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                throw new \Exception('NIN verification failed: ' . $response->status());
            }

            $data = $response->json();

            Log::info('NIN verification successful', [
                'nin' => $nin,
                'response' => $data,
            ]);

            return $data;

        } catch (\Exception $e) {
            Log::error('NIN verification error', [
                'nin' => $nin,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('NIN verification error: ' . $e->getMessage());
        }
    }

    /**
     * Get NIN verification status
     *
     * @param string $nin
     * @return bool
     */
    public function isNINValid(string $nin): bool
    {
        try {
            $result = $this->verifyNIN($nin);
            return isset($result['status']) && $result['status'] === 'success';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Extract member data from verification response
     *
     * @param array $verificationData
     * @param string $originalNin
     * @return array
     */
    public function extractMemberData(array $verificationData, string $originalNin): array
    {
        if (!isset($verificationData['data'])) {
            return [
                'nin' => null,
                'first_name' => null,
                'last_name' => null,
                'gender' => null,
                'date_of_birth' => null,
                'phone_number' => null,
                'email' => null,
                'ward' => null,
                'polling_unit' => null,
                'residential_address' => null,
            ];
        }

        $data = $verificationData['data'];
        $environment = config('services.prembly.env', 'test');
        
        // Use original NIN for test mode, verified NIN for live mode
        $ninToUse = ($environment === 'test') ? $originalNin : ($data['nin'] ?? null);

        return [
            'nin' => $ninToUse,
            'first_name' => $data['firstname'] ?? null,
            'last_name' => $data['surname'] ?? null,
            'gender' => $data['gender'] ?? null,
            'date_of_birth' => $this->formatDate($data['birthdate'] ?? null),
            'phone_number' => $data['telephoneno'] ?? null,
            'email' => null, // Not provided in response
            'ward' => null, // Not provided in response
            'polling_unit' => null, // Not provided in response
            'residential_address' => $data['residence_address'] ?? null,
            // Verification metadata
            'verification_status' => $verificationData['verification']['status'] ?? null,
            'verification_reference' => $verificationData['verification']['reference'] ?? null,
            'verification_id' => $verificationData['verification']['verification_id'] ?? null,
        ];
    }
    
    /**
     * Format date from API response (DD-MM-YYYY) to database format (YYYY-MM-DD)
     *
     * @param string|null $dateString
     * @return string|null
     */
    private function formatDate(?string $dateString): ?string
    {
        if (!$dateString) {
            return null;
        }
        
        // Convert from DD-MM-YYYY to YYYY-MM-DD
        $parts = explode('-', $dateString);
        if (count($parts) === 3) {
            return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
        
        return $dateString;
    }
    
    /**
     * Get fields that can be populated from NIN verification
     *
     * @return array
     */
    public function getAvailableFields(): array
    {
        return [
            'nin',
            'first_name', 
            'last_name',
            'gender',
            'date_of_birth',
            'phone_number',
            'residential_address'
        ];
    }
    
    /**
     * Get fields that need to be collected manually
     *
     * @return array
     */
    public function getRequiredManualFields(): array
    {
        return [
            'email',
            'state',
            'lga', 
            'ward',
            'polling_unit',
            'membership_number',
            'agentcode',
            'registration_date'
        ];
    }
    
    /**
     * Batch verify NINs with rate limiting and error handling
     *
     * @param array $nins
     * @return array
     */
    public function batchVerifyNINs(array $nins): array
    {
        $results = [
            'total' => count($nins),
            'verified' => 0,
            'failed' => 0,
            'data' => [],
            'errors' => []
        ];

        foreach ($nins as $nin) {
            try {
                $verificationData = $this->verifyNIN($nin);
                $memberData = $this->extractMemberData($verificationData);
                
                if ($memberData['nin']) {
                    $results['verified']++;
                    $results['data'][$nin] = $memberData;
                } else {
                    $results['failed']++;
                    $results['errors'][$nin] = 'Invalid verification response';
                }
                
                // Add delay to respect API rate limits (if any)
                usleep(100000); // 100ms delay between requests
                
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][$nin] = $e->getMessage();
                
                Log::error("Batch NIN verification failed for {$nin}", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }
    
    /**
     * Validate NIN format before API call
     *
     * @param string $nin
     * @return bool
     */
    public function validateNINFormat(string $nin): bool
    {
        // Remove any whitespace
        $nin = trim($nin);
        
        // Check if it's 10 or 11 digits (accepting both formats)
        return preg_match('/^\d{10,11}$/', $nin);
    }
    
    /**
     * Clean and format NIN
     *
     * @param string $nin
     * @return string|null
     */
    public function cleanNIN(string $nin): ?string
    {
        // Remove whitespace, hyphens, and other non-digit characters
        $cleaned = preg_replace('/[^0-9]/', '', $nin);
        
        // Return only if it's a valid 10 or 11-digit NIN
        return $this->validateNINFormat($cleaned) ? $cleaned : null;
    }
}
