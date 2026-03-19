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

            // ✅ Safe check
            if ($response->successful() && isset($responseData['status']) && $responseData['status'] === true) {

                Log::info('Paystack: List Banks Successful', [
                    'banks_count' => count($responseData['data'] ?? [])
                ]);

                return response()->json([
                    'status' => true,
                    'data' => $responseData['data'] ?? []
                ]);
            }

            Log::error('Paystack: List Banks Failed', [
                'status_code' => $response->status(),
                'raw_body' => $rawBody
            ]);

            return response()->json([
                'status' => false,
                'message' => $responseData['message'] ?? 'Failed to fetch banks'
            ], 400);

        } catch (\Throwable $e) {

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('Paystack: List Banks Exception', [
                'error_message' => $e->getMessage(),
                'response_time_ms' => $responseTime,
                'timestamp' => now()->toISOString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Error fetching banks'
            ], 500);
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

            return response()->json([
                'status' => false,
                'message' => $responseData['message'] ?? 'Failed to resolve account'
            ], 400);

        } catch (\Throwable $e) {

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('Paystack: Resolve Exception', [
                'error_message' => $e->getMessage(),
                'response_time_ms' => $responseTime,
                'timestamp' => now()->toISOString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Error resolving account'
            ], 500);
        }
    }
}