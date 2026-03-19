<?php

namespace App\Http\Controllers;

use App\Models\ExternalMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ExternalMembersController extends Controller
{
    /**
     * Get all members from external database
     */
    public function index(Request $request)
    {
        $startTime = microtime(true);
        
        Log::info('External Members Fetch Request Started', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString()
        ]);

        try {
            // Test database connection first
            DB::connection('external_mysql')->getPdo();
            
            $query = ExternalMember::query();
            
            // Apply filters if provided
            if ($request->has('state')) {
                $query->where('state', 'like', '%' . $request->state . '%');
            }
            
            if ($request->has('lga')) {
                $query->where('lga', 'like', '%' . $request->lga . '%');
            }
            
            if ($request->has('ward')) {
                $query->where('ward', 'like', '%' . $request->ward . '%');
            }
            
            if ($request->has('agentcode')) {
                $query->where('agentcode', $request->agentcode);
            }
            
            // Pagination
            $perPage = $request->get('per_page', 50);
            $page = $request->get('page', 1);
            
            $members = $query->orderBy('registration_date', 'desc')
                            ->paginate($perPage, ['*'], 'page', $page);
            
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('External Members Fetch Successful', [
                'members_count' => $members->count(),
                'total_count' => $members->total(),
                'current_page' => $members->currentPage(),
                'per_page' => $perPage,
                'response_time_ms' => $responseTime,
                'timestamp' => now()->toISOString()
            ]);

            return response()->json([
                'success' => true,
                'data' => $members->items(),
                'pagination' => [
                    'current_page' => $members->currentPage(),
                    'per_page' => $members->perPage(),
                    'total' => $members->total(),
                    'last_page' => $members->lastPage(),
                    'from' => $members->firstItem(),
                    'to' => $members->lastItem(),
                ],
                'response_time_ms' => $responseTime
            ]);
            
        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error('External Members Fetch Failed', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'response_time_ms' => $responseTime,
                'timestamp' => now()->toISOString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch members from external database',
                'error' => app()->environment('local') ? $e->getMessage() : 'Database connection error'
            ], 500);
        }
    }

    /**
     * Get a specific member by ID
     */
    public function show(Request $request, $id)
    {
        $startTime = microtime(true);
        
        Log::info('External Member Detail Request Started', [
            'member_id' => $id,
            'ip' => $request->ip(),
            'timestamp' => now()->toISOString()
        ]);

        try {
            DB::connection('external_mysql')->getPdo();
            
            $member = ExternalMember::find($id);
            
            if (!$member) {
                Log::warning('External Member Not Found', [
                    'member_id' => $id,
                    'timestamp' => now()->toISOString()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Member not found'
                ], 404);
            }
            
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('External Member Detail Successful', [
                'member_id' => $id,
                'membership_number' => $member->membership_number,
                'response_time_ms' => $responseTime,
                'timestamp' => now()->toISOString()
            ]);

            return response()->json([
                'success' => true,
                'data' => $member,
                'response_time_ms' => $responseTime
            ]);
            
        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error('External Member Detail Failed', [
                'member_id' => $id,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'response_time_ms' => $responseTime,
                'timestamp' => now()->toISOString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch member details',
                'error' => app()->environment('local') ? $e->getMessage() : 'Database error'
            ], 500);
        }
    }

    /**
     * Get members statistics
     */
    public function statistics(Request $request)
    {
        $startTime = microtime(true);
        
        Log::info('External Members Statistics Request Started', [
            'ip' => $request->ip(),
            'timestamp' => now()->toISOString()
        ]);

        try {
            DB::connection('external_mysql')->getPdo();
            
            $stats = [
                'total_members' => ExternalMember::count(),
                'members_by_state' => ExternalMember::select('state', DB::raw('count(*) as count'))
                                        ->groupBy('state')
                                        ->orderBy('count', 'desc')
                                        ->get(),
                'members_by_lga' => ExternalMember::select('lga', DB::raw('count(*) as count'))
                                       ->groupBy('lga')
                                       ->orderBy('count', 'desc')
                                       ->limit(20)
                                       ->get(),
                'recent_registrations' => ExternalMember::orderBy('registration_date', 'desc')
                                                   ->limit(10)
                                                   ->get(['id', 'first_name', 'last_name', 'membership_number', 'registration_date']),
                'gender_distribution' => ExternalMember::select('gender', DB::raw('count(*) as count'))
                                                  ->groupBy('gender')
                                                  ->get(),
            ];
            
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('External Members Statistics Successful', [
                'total_members' => $stats['total_members'],
                'response_time_ms' => $responseTime,
                'timestamp' => now()->toISOString()
            ]);

            return response()->json([
                'success' => true,
                'data' => $stats,
                'response_time_ms' => $responseTime
            ]);
            
        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error('External Members Statistics Failed', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'response_time_ms' => $responseTime,
                'timestamp' => now()->toISOString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => app()->environment('local') ? $e->getMessage() : 'Database error'
            ], 500);
        }
    }

    /**
     * Test external database connection
     */
    public function testConnection()
    {
        try {
            // Log the database configuration (without password for security)
            $dbConfig = config('database.connections.external_mysql');
            
            Log::info('External Database Configuration', [
                'driver' => $dbConfig['driver'] ?? 'not_set',
                'host' => $dbConfig['host'] ?? 'not_set',
                'port' => $dbConfig['port'] ?? 'not_set',
                'database' => $dbConfig['database'] ?? 'not_set',
                'username' => $dbConfig['username'] ?? 'not_set',
                'password_set' => !empty($dbConfig['password']),
                'charset' => $dbConfig['charset'] ?? 'not_set',
                'collation' => $dbConfig['collation'] ?? 'not_set',
                'timestamp' => now()->toISOString()
            ]);

            // Log environment variables (without password)
            Log::info('Environment Variables for External DB', [
                'DB_EXTERNAL_HOST' => env('DB_EXTERNAL_HOST', 'not_set'),
                'DB_EXTERNAL_PORT' => env('DB_EXTERNAL_PORT', 'not_set'),
                'DB_EXTERNAL_DATABASE' => env('DB_EXTERNAL_DATABASE', 'not_set'),
                'DB_EXTERNAL_USERNAME' => env('DB_EXTERNAL_USERNAME', 'not_set'),
                'DB_EXTERNAL_PASSWORD_SET' => !empty(env('DB_EXTERNAL_PASSWORD')),
                'timestamp' => now()->toISOString()
            ]);

            DB::connection('external_mysql')->getPdo();
            
            // Test a simple query
            $count = ExternalMember::count();
            
            Log::info('External Database Connection Test Successful', [
                'total_members' => $count,
                'timestamp' => now()->toISOString()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'External database connection successful',
                'data' => [
                    'database' => config('database.connections.external_mysql.database'),
                    'total_members' => $count,
                    'connection_status' => 'connected'
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('External Database Connection Test Failed', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'timestamp' => now()->toISOString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'External database connection failed',
                'error' => app()->environment('local') ? $e->getMessage() : 'Connection error',
                'debug_info' => [
                    'config_loaded' => config('database.connections.external_mysql') !== null,
                    'env_host' => env('DB_EXTERNAL_HOST'),
                    'env_database' => env('DB_EXTERNAL_DATABASE'),
                    'env_username' => env('DB_EXTERNAL_USERNAME'),
                    'env_password_set' => !empty(env('DB_EXTERNAL_PASSWORD')),
                ]
            ], 500);
        }
    }
}
