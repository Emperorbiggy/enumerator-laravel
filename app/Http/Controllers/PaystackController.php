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
                'headers' => array_merge($headers, ['Authorization' => 'Bearer [REDACTED]']),
                'params' => ['country' => $requestData['country'], 'perPage' => $requestData['perPage']]
            ]);

            $response = Http::withHeaders($headers)->get($url, [
                'country' => $requestData['country'],
                'perPage' => $requestData['perPage'],
            ]);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            $responseData = $response->json();

            Log::info('Paystack: List Banks Response Received', [
                'status_code' => $response->status(),
                'response_time_ms' => $responseTime,
                'response_data' => $responseData,
                'success' => $response->successful(),
                'timestamp' => now()->toISOString()
            ]);

            if ($responseData['status']) {
                Log::info('Paystack: List Banks Successful', [
                    'banks_count' => count($responseData['data']),
                    'banks' => array_map(function($bank) {
                        return [
                            'name' => $bank['name'],
                            'code' => $bank['code'],
                            'active' => $bank['active'] ?? null
                        ];
                    }, $responseData['data'])
                ]);

                return response()->json([
                    'status' => true,
                    'data' => $responseData['data']
                ]);
            }

            Log::error('Paystack: List Banks Failed', [
                'error_message' => $responseData['message'] ?? 'Unknown error',
                'full_response' => $responseData
            ]);

            return response()->json([
                'status' => false,
                'message' => $responseData['message'] ?? 'Failed to fetch banks'
            ], 400);

        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error('Paystack: List Banks Exception', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'response_time_ms' => $responseTime,
                'request_data' => $requestData,
                'timestamp' => now()->toISOString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Error fetching banks: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resolve account number
     */
    public function resolveAccount(Request $request)
    {
        $startTime = microtime(true);
        $requestData = [
            'account_number' => $request->get('account_number'),
            'bank_code' => $request->get('bank_code'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ];

        Log::info('Paystack: Resolve Account Request Started', [
            'request_data' => [
                'account_number' => $requestData['account_number'],
                'bank_code' => $requestData['bank_code'],
                'ip' => $requestData['ip']
            ],
            'timestamp' => now()->toISOString()
        ]);

        $request->validate([
            'account_number' => 'required|string|digits:10',
            'bank_code' => 'required|string'
        ]);

        try {
            $url = 'https://api.paystack.co/bank/resolve';
            $headers = [
                'Authorization' => 'Bearer ' . Config::get('services.paystack.secret_key'),
                'Content-Type' => 'application/json',
            ];

            Log::info('Paystack: Making Account Verification API Request', [
                'url' => $url,
                'headers' => array_merge($headers, ['Authorization' => 'Bearer [REDACTED]']),
                'params' => [
                    'account_number' => $requestData['account_number'],
                    'bank_code' => $requestData['bank_code']
                ]
            ]);

            $response = Http::withHeaders($headers)->get($url, [
                'account_number' => $requestData['account_number'],
                'bank_code' => $requestData['bank_code'],
            ]);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            $responseData = $response->json();

            Log::info('Paystack: Resolve Account Response Received', [
                'status_code' => $response->status(),
                'response_time_ms' => $responseTime,
                'response_data' => $responseData,
                'success' => $response->successful(),
                'timestamp' => now()->toISOString()
            ]);

            if ($responseData['status']) {
                Log::info('Paystack: Account Resolution Successful', [
                    'account_number' => $responseData['data']['account_number'],
                    'account_name' => $responseData['data']['account_name'],
                    'bank_code' => $requestData['bank_code'],
                    'verification_time_ms' => $responseTime
                ]);

                return response()->json([
                    'status' => true,
                    'data' => $responseData['data']
                ]);
            }

            Log::error('Paystack: Account Resolution Failed', [
                'error_message' => $responseData['message'] ?? 'Unknown error',
                'account_number' => $requestData['account_number'],
                'bank_code' => $requestData['bank_code'],
                'full_response' => $responseData
            ]);

            return response()->json([
                'status' => false,
                'message' => $responseData['message'] ?? 'Failed to resolve account'
            ], 400);

        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error('Paystack: Resolve Account Exception', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'response_time_ms' => $responseTime,
                'request_data' => [
                    'account_number' => $requestData['account_number'],
                    'bank_code' => $requestData['bank_code']
                ],
                'timestamp' => now()->toISOString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Error resolving account: ' . $e->getMessage()
            ], 500);
        }
    }
}
