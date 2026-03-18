<?php

namespace App\Http\Controllers;

use App\Models\Enumerator;
use App\Services\EnumeratorDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class EnumeratorController extends Controller
{
    protected $dataService;

    public function __construct(EnumeratorDataService $dataService)
    {
        $this->dataService = $dataService;
    }

    /**
     * Get all LGAs
     */
    public function getLGAs()
    {
        try {
            $lgas = $this->dataService->getLGAs();
            return response()->json([
                'success' => true,
                'data' => $lgas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch LGAs'
            ], 500);
        }
    }

    /**
     * Get wards by LGA
     */
    public function getWardsByLGA(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lga' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'LGA name is required'
            ], 400);
        }

        try {
            $wards = $this->dataService->getWardsByLGA($request->lga);
            
            // If no specific wards found, return default ones
            if (empty($wards)) {
                $wards = $this->dataService->getDefaultPollingUnits($request->lga);
            }

            return response()->json([
                'success' => true,
                'data' => $wards
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch wards'
            ], 500);
        }
    }

    /**
     * Get polling units by ward
     */
    public function getPollingUnitsByWard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ward' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ward name is required'
            ], 400);
        }

        try {
            $pollingUnits = $this->dataService->getPollingUnitsByWard($request->ward);
            
            // If no specific polling units found, return default ones
            if (empty($pollingUnits)) {
                $pollingUnits = $this->dataService->getDefaultPollingUnits($request->ward);
            }

            return response()->json([
                'success' => true,
                'data' => $pollingUnits
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch polling units'
            ], 500);
        }
    }

    /**
     * Register a new enumerator
     */
    public function register(Request $request)
    {
        Log::info('Enumerator Registration Request Started', [
            'email' => $request->email,
            'full_name' => $request->full_name,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'is_inertia' => $request->inertia(),
            'expects_json' => $request->expectsJson(),
            'timestamp' => now()->toISOString()
        ]);

        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:enumerators,email',
            'whatsapp' => 'required|string|max:20',
            'state' => 'required|string|max:100',
            'lga' => 'required|string|max:100',
            'ward' => 'required|string|max:200',
            'polling_unit' => 'required|string|max:200',
            'browsing_network' => 'required|string|max:50',
            'browsing_number' => 'required|string|max:20',
            'bank_name' => 'required|string|max:100',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|size:10',
            'group_name' => 'required|string|max:255',
            'coordinator_phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            Log::warning('Enumerator Registration Validation Failed', [
                'email' => $request->email,
                'errors' => $validator->errors()->toArray(),
                'timestamp' => now()->toISOString()
            ]);

            if ($request->inertia()) {
                return redirect()
                    ->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Generate unique enumerator code
            $lastCode = Enumerator::orderBy('id', 'desc')->value('code');
            $nextNumber = $lastCode ? (int) $lastCode + 1 : 1;
            $code = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            $enumerator = Enumerator::create([
                'code' => $code,
                'full_name' => $request->full_name,
                'email' => $request->email,
                'whatsapp' => $request->whatsapp,
                'state' => $request->state,
                'lga' => $request->lga,
                'ward' => $request->ward,
                'polling_unit' => $request->polling_unit,
                'browsing_network' => $request->browsing_network,
                'browsing_number' => $request->browsing_number,
                'bank_name' => $request->bank_name,
                'account_name' => $request->account_name,
                'account_number' => $request->account_number,
                'group_name' => $request->group_name,
                'coordinator_phone' => $request->coordinator_phone,
                'registered_at' => now(),
            ]);

            Log::info('Enumerator Registration Successful', [
                'enumerator_id' => $enumerator->id,
                'code' => $enumerator->code,
                'email' => $enumerator->email,
                'full_name' => $enumerator->full_name,
                'timestamp' => now()->toISOString()
            ]);

            $successData = [
                'id' => $enumerator->id,
                'code' => $enumerator->code,
                'full_name' => $enumerator->full_name,
                'email' => $enumerator->email,
                'registered_at' => $enumerator->registered_at,
            ];

            if ($request->inertia()) {
                return redirect()
                    ->route('register')
                    ->with('success', true)
                    ->with('data', $successData);
            }

            return response()->json([
                'success' => true,
                'message' => 'Registration successful!',
                'data' => $successData
            ], 201);

        } catch (\Exception $e) {
            Log::error('Enumerator Registration Failed', [
                'email' => $request->email,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'timestamp' => now()->toISOString()
            ]);

            if ($request->inertia()) {
                return redirect()
                    ->back()
                    ->with('error', 'Registration failed. Please try again.')
                    ->withInput();
            }

            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Get total count of registered enumerators
     */
    public function getCount()
    {
        try {
            $count = Enumerator::count();
            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $count
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get enumerator count'
            ], 500);
        }
    }
}
