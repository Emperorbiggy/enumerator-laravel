<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class PaystackController extends Controller
{
    /**
     * Get list of banks from Paystack
     */
    public function listBanks(Request $request)
    {
        $startTime = microtime(true);

        $requestData = [
            'country' => $request->get('country', 'nigeria'),
            'perPage' => $request->get('perPage', 100),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ];

        Log::info('Paystack: List Banks Request Started', [
            'request_data' => $requestData,
            'timestamp' => now()->toISOString()
        ]);

        try {
            $url = 'https://api.paystack.co/bank';

            $headers = [
                'Authorization' => 'Bearer ' . Config::get('services.paystack.secret_key'),
                'Content-Type' => 'application/json',
            ];

            Log::info('Paystack: Making API Request', [
                'url' => $url,
                'headers' => ['Authorization' => 'Bearer [REDACTED]'],
                'params' => [
                    'country' => $requestData['country'],
                    'perPage' => $requestData['perPage']
                ]
            ]);

            $response = Http::withHeaders($headers)->get($url, [
                'country' => $requestData['country'],
                'perPage' => $requestData['perPage'],
            ]);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            $rawBody = $response->body();
            $responseData = $response->json();

            // ✅ Log RAW + Parsed response (safe for debugging)
            Log::info('Paystack: List Banks Response Received', [
                'status_code' => $response->status(),
                'response_time_ms' => $responseTime,
                'response_data' => $responseData,
                'success' => $response->successful(),
                'timestamp' => now()->toISOString()
            ]);

            // Only log full response in local/dev
            if (app()->environment('local')) {
                Log::debug('Paystack RAW Response (Banks)', [
                    'raw_body' => $rawBody,
                    'parsed_json' => $responseData
                ]);
            }

            // Check if response is successful and has expected structure
            if ($response->successful() && isset($responseData['status']) && $responseData['status'] === true) {
                Log::info('Paystack: List Banks Successful', [
                    'banks_count' => isset($responseData['data']) ? count($responseData['data']) : 0,
                    'response_structure' => array_keys($responseData)
                ]);

                return response()->json([
                    'status' => true,
                    'data' => $responseData['data'] ?? []
                ]);
            }

            // Handle API failure responses - Try Flutterwave fallback
            Log::error('Paystack: List Banks Failed', [
                'error_message' => $responseData['message'] ?? 'API request failed',
                'status_code' => $response->status(),
                'response_structure' => array_keys($responseData),
                'full_response' => $responseData
            ]);

            // Try Flutterwave as fallback
            $flutterwaveResponse = $this->listBanksFromFlutterwave($request);
            if ($flutterwaveResponse) {
                return $flutterwaveResponse;
            }

            // If Flutterwave also fails, use hardcoded Nigerian banks
            $fallbackBanks = [
                ['name' => 'Access Bank', 'code' => '044', 'active' => true],
                ['name' => 'Citibank Nigeria', 'code' => '023', 'active' => true],
                ['name' => 'Ecobank Nigeria', 'code' => '050', 'active' => true],
                ['name' => 'Fidelity Bank Nigeria', 'code' => '070', 'active' => true],
                ['name' => 'First Bank of Nigeria', 'code' => '011', 'active' => true],
                ['name' => 'First City Monument Bank', 'code' => '056', 'active' => true],
                ['name' => 'Globus Bank Nigeria', 'code' => '001', 'active' => true],
                ['name' => 'Guaranty Trust Bank', 'code' => '058', 'active' => true],
                ['name' => 'Heritage Bank Nigeria', 'code' => '030', 'active' => true],
                ['name' => 'Jaiz Bank', 'code' => '301', 'active' => true],
                ['name' => 'Keystone Bank Nigeria', 'code' => '082', 'active' => true],
                ['name' => 'Polaris Bank Nigeria', 'code' => '076', 'active' => true],
                ['name' => 'Providus Bank', 'code' => '101', 'active' => true],
                ['name' => 'Stanbic IBTC Bank', 'code' => '068', 'active' => true],
                ['name' => 'Standard Chartered Bank', 'code' => '068', 'active' => true],
                ['name' => 'Sterling Bank Nigeria', 'code' => '232', 'active' => true],
                ['name' => 'Union Bank of Nigeria', 'code' => '032', 'active' => true],
                ['name' => 'United Bank for Africa', 'code' => '033', 'active' => true],
                ['name' => 'Unity Bank Nigeria', 'code' => '215', 'active' => true],
                ['name' => 'Wema Bank Nigeria', 'code' => '035', 'active' => true],
                ['name' => 'Zenith Bank Nigeria', 'code' => '057', 'active' => true],
            ];

            Log::info('Paystack: Using Fallback Banks', [
                'banks_count' => count($fallbackBanks),
                'fallback_reason' => 'Both Paystack and Flutterwave APIs failed'
            ]);

            return response()->json([
                'status' => true,
                'data' => $fallbackBanks,
                'message' => 'Using fallback bank list (APIs unavailable)',
                'fallback_used' => true,
                'provider' => 'hardcoded'
            ]);

        } catch (\Throwable $e) {

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('Paystack: List Banks Exception', [
                'error_message' => $e->getMessage(),
                'response_time_ms' => $responseTime,
                'timestamp' => now()->toISOString()
            ]);

            // Try Flutterwave as fallback on exceptions
            $flutterwaveResponse = $this->listBanksFromFlutterwave($request);
            if ($flutterwaveResponse) {
                return $flutterwaveResponse;
            }

            // If Flutterwave also fails, use hardcoded Nigerian banks
            $fallbackBanks = [
                ['name' => 'Access Bank', 'code' => '044', 'active' => true],
                ['name' => 'Citibank Nigeria', 'code' => '023', 'active' => true],
                ['name' => 'Ecobank Nigeria', 'code' => '050', 'active' => true],
                ['name' => 'Fidelity Bank Nigeria', 'code' => '070', 'active' => true],
                ['name' => 'First Bank of Nigeria', 'code' => '011', 'active' => true],
                ['name' => 'First City Monument Bank', 'code' => '056', 'active' => true],
                ['name' => 'Globus Bank Nigeria', 'code' => '001', 'active' => true],
                ['name' => 'Guaranty Trust Bank', 'code' => '058', 'active' => true],
                ['name' => 'Heritage Bank Nigeria', 'code' => '030', 'active' => true],
                ['name' => 'Jaiz Bank', 'code' => '301', 'active' => true],
                ['name' => 'Keystone Bank Nigeria', 'code' => '082', 'active' => true],
                ['name' => 'Polaris Bank Nigeria', 'code' => '076', 'active' => true],
                ['name' => 'Providus Bank', 'code' => '101', 'active' => true],
                ['name' => 'Stanbic IBTC Bank', 'code' => '068', 'active' => true],
                ['name' => 'Standard Chartered Bank', 'code' => '068', 'active' => true],
                ['name' => 'Sterling Bank Nigeria', 'code' => '232', 'active' => true],
                ['name' => 'Union Bank of Nigeria', 'code' => '032', 'active' => true],
                ['name' => 'United Bank for Africa', 'code' => '033', 'active' => true],
                ['name' => 'Unity Bank Nigeria', 'code' => '215', 'active' => true],
                ['name' => 'Wema Bank Nigeria', 'code' => '035', 'active' => true],
                ['name' => 'Zenith Bank Nigeria', 'code' => '057', 'active' => true],
            ];

            Log::info('Paystack: Using Fallback Banks (Exception)', [
                'banks_count' => count($fallbackBanks),
                'exception' => $e->getMessage(),
                'fallback_reason' => 'Both Paystack and Flutterwave APIs failed'
            ]);

            return response()->json([
                'status' => true,
                'data' => $fallbackBanks,
                'message' => 'Using fallback bank list (APIs unavailable)',
                'fallback_used' => true,
                'provider' => 'hardcoded'
            ]);
        }
    }

    /**
     * Get list of banks from Flutterwave (fallback)
     */
    private function listBanksFromFlutterwave(Request $request)
    {
        $startTime = microtime(true);

        Log::info('Flutterwave: List Banks Request Started (Fallback)', [
            'timestamp' => now()->toISOString()
        ]);

        try {
            $url = 'https://api.flutterwave.com/v3/banks/NG';

            $headers = [
                'Authorization' => 'Bearer ' . Config::get('services.flutterwave.secret_key'),
                'Content-Type' => 'application/json',
                'accept' => 'application/json'
            ];

            Log::info('Flutterwave: Making API Request', [
                'url' => $url,
                'headers' => ['Authorization' => 'Bearer [REDACTED]']
            ]);

            $response = Http::withHeaders($headers)->get($url, [
                'include_provider_type' => 1
            ]);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            $responseData = $response->json();

            Log::info('Flutterwave: List Banks Response Received', [
                'status_code' => $response->status(),
                'response_time_ms' => $responseTime,
                'success' => $response->successful(),
                'timestamp' => now()->toISOString()
            ]);

            if ($response->successful() && isset($responseData['status']) && $responseData['status'] === 'success') {
                // Transform Flutterwave response to match Paystack format
                $banks = [];
                foreach ($responseData['data'] as $bank) {
                    $banks[] = [
                        'name' => $bank['name'],
                        'code' => $bank['code'],
                        'active' => true
                    ];
                }

                Log::info('Flutterwave: List Banks Successful', [
                    'banks_count' => count($banks),
                    'fallback_used' => true
                ]);

                return response()->json([
                    'status' => true,
                    'data' => $banks,
                    'fallback_used' => true,
                    'provider' => 'flutterwave'
                ]);
            }

            Log::error('Flutterwave: List Banks Failed', [
                'error_message' => $responseData['message'] ?? 'API request failed',
                'status_code' => $response->status()
            ]);

            return null;

        } catch (\Throwable $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('Flutterwave: List Banks Exception', [
                'error_message' => $e->getMessage(),
                'response_time_ms' => $responseTime,
                'timestamp' => now()->toISOString()
            ]);

            return null;
        }
    }

    /**
     * Resolve account number using Flutterwave (fallback)
     */
    private function resolveAccountFromFlutterwave(Request $request)
    {
        $startTime = microtime(true);

        Log::info('Flutterwave: Resolve Account Request Started (Fallback)', [
            'account_number' => $request->get('account_number'),
            'bank_code' => $request->get('bank_code'),
            'timestamp' => now()->toISOString()
        ]);

        try {
            $url = 'https://api.flutterwave.com/v3/accounts/resolve';

            $headers = [
                'Authorization' => 'Bearer ' . Config::get('services.flutterwave.secret_key'),
                'Content-Type' => 'application/json',
                'accept' => 'application/json'
            ];

            $data = [
                'account_number' => $request->get('account_number'),
                'account_bank' => $request->get('bank_code')
            ];

            Log::info('Flutterwave: Making Account Resolve Request', [
                'url' => $url,
                'headers' => ['Authorization' => 'Bearer [REDACTED]'],
                'data' => $data
            ]);

            $response = Http::withHeaders($headers)->post($url, $data);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            $responseData = $response->json();

            Log::info('Flutterwave: Resolve Response Received', [
                'status_code' => $response->status(),
                'response_time_ms' => $responseTime,
                'success' => $response->successful(),
                'timestamp' => now()->toISOString()
            ]);

            if ($response->successful() && isset($responseData['status']) && $responseData['status'] === 'success') {
                // Transform Flutterwave response to match Paystack format
                $paystackFormat = [
                    'account_number' => $responseData['data']['account_number'],
                    'account_name' => $responseData['data']['account_name'],
                    'bank_id' => $responseData['data']['bank_id']
                ];

                Log::info('Flutterwave: Resolve Account Successful', [
                    'account_name' => $responseData['data']['account_name'],
                    'fallback_used' => true
                ]);

                return response()->json([
                    'status' => true,
                    'data' => $paystackFormat,
                    'fallback_used' => true,
                    'provider' => 'flutterwave'
                ]);
            }

            Log::error('Flutterwave: Resolve Failed', [
                'error_message' => $responseData['message'] ?? 'API request failed',
                'status_code' => $response->status()
            ]);

            return null;

        } catch (\Throwable $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('Flutterwave: Resolve Exception', [
                'error_message' => $e->getMessage(),
                'response_time_ms' => $responseTime,
                'timestamp' => now()->toISOString()
            ]);

            return null;
        }
    }

    /**
     * Resolve account number
     */
    public function resolveAccount(Request $request)
    {
        $startTime = microtime(true);

        $request->validate([
            'account_number' => 'required|string|digits:10',
            'bank_code' => 'required|string'
        ]);

        $requestData = [
            'account_number' => $request->get('account_number'),
            'bank_code' => $request->get('bank_code'),
            'ip' => $request->ip()
        ];

        Log::info('Paystack: Resolve Account Request Started', [
            'request_data' => $requestData,
            'timestamp' => now()->toISOString()
        ]);

        try {
            $url = 'https://api.paystack.co/bank/resolve';

            $headers = [
                'Authorization' => 'Bearer ' . Config::get('services.paystack.secret_key'),
                'Content-Type' => 'application/json',
            ];

            Log::info('Paystack: Making Account Resolve Request', [
                'url' => $url,
                'headers' => ['Authorization' => 'Bearer [REDACTED]'],
                'params' => $requestData
            ]);

            $response = Http::withHeaders($headers)->get($url, [
                'account_number' => $requestData['account_number'],
                'bank_code' => $requestData['bank_code'],
            ]);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            $rawBody = $response->body();
            $responseData = $response->json();

            Log::info('Paystack: Resolve Response Received', [
                'status_code' => $response->status(),
                'response_time_ms' => $responseTime,
                'success' => $response->successful(),
                'timestamp' => now()->toISOString()
            ]);

            // Debug only in local
            if (app()->environment('local')) {
                Log::debug('Paystack RAW Response (Resolve)', [
                    'raw_body' => $rawBody,
                    'parsed_json' => $responseData
                ]);
            }

            if ($response->successful() && isset($responseData['status']) && $responseData['status'] === true) {

                return response()->json([
                    'status' => true,
                    'data' => $responseData['data']
                ]);
            }

            Log::error('Paystack: Resolve Failed', [
                'status_code' => $response->status(),
                'raw_body' => $rawBody
            ]);

            // Try Flutterwave as fallback
            $flutterwaveResponse = $this->resolveAccountFromFlutterwave($request);
            if ($flutterwaveResponse) {
                return $flutterwaveResponse;
            }

            return response()->json([
                'status' => false,
                'message' => $responseData['message'] ?? 'Failed to resolve account (both Paystack and Flutterwave failed)'
            ], 400);

        } catch (\Throwable $e) {

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('Paystack: Resolve Exception', [
                'error_message' => $e->getMessage(),
                'response_time_ms' => $responseTime,
                'timestamp' => now()->toISOString()
            ]);

            // Try Flutterwave as fallback on exceptions
            $flutterwaveResponse = $this->resolveAccountFromFlutterwave($request);
            if ($flutterwaveResponse) {
                return $flutterwaveResponse;
            }

            return response()->json([
                'status' => false,
                'message' => 'Error resolving account (both Paystack and Flutterwave failed)'
            ], 500);
        }
    }
}