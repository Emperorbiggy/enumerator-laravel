<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Enumerator;
use App\Models\ExternalMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function showLoginForm()
    {
        return Inertia::render('Admin/Login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('admin')->attempt($credentials)) {
            $request->session()->regenerate();
            
            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        // Since no authentication is required, just redirect to dashboard
        return redirect()->route('admin.dashboard');
    }

    public function dashboard()
    {
        $stats = [
            'total_enumerators' => Enumerator::count(),
            'today_registrations' => Enumerator::whereDate('registered_at', today())->count(),
            'this_week_registrations' => Enumerator::whereBetween('registered_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'this_month_registrations' => Enumerator::whereMonth('registered_at', now()->month)
                ->whereYear('registered_at', now()->year)
                ->count(),
        ];

        // LGAs with most registrations
        $topLgas = Enumerator::select('lga')
            ->selectRaw('count(*) as count')
            ->groupBy('lga')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // Recent registrations
        $recentEnumerators = Enumerator::latest('registered_at')
            ->take(10)
            ->get(['id', 'code', 'full_name', 'email', 'whatsapp', 'lga', 'ward', 'registered_at']);

        // Registration trends for the last 7 days
        $registrationTrends = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $registrationTrends[] = [
                'date' => $date->format('M j'),
                'count' => Enumerator::whereDate('registered_at', $date)->count()
            ];
        }

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'topLgas' => $topLgas,
            'recentEnumerators' => $recentEnumerators,
            'registrationTrends' => $registrationTrends,
        ]);
    }

    /**
     * Get enumerator performance statistics
     */
    public function enumeratorPerformance(Request $request)
    {
        $startTime = microtime(true);
        
        Log::info('Admin: Enumerator Performance Request Started', [
            'ip' => $request->ip(),
            'timestamp' => now()->toISOString()
        ]);

        try {
            // Try using the relationship first
            try {
                // Get all enumerators with their member counts
                $enumerators = Enumerator::select('id', 'code', 'full_name', 'email', 'whatsapp', 'lga', 'ward', 'registered_at')
                    ->withCount(['externalMembers as members_registered'])
                    ->orderBy('members_registered', 'desc')
                    ->paginate(50);

                // Get performance statistics
                $stats = [
                    'total_enumerators' => Enumerator::count(),
                    'total_members_registered' => ExternalMember::count(),
                    'enumerators_with_members' => Enumerator::whereHas('externalMembers')->count(),
                    'enumerators_without_members' => Enumerator::whereDoesntHave('externalMembers')->count(),
                    'average_members_per_enumerator' => ExternalMember::count() / max(Enumerator::count(), 1),
                    'top_performer' => Enumerator::withCount('externalMembers as members_registered')
                        ->orderBy('members_registered', 'desc')
                        ->first(),
                ];

                // Top performers
                $topPerformers = Enumerator::withCount('externalMembers as members_registered')
                    ->orderBy('members_registered', 'desc')
                    ->limit(10)
                    ->get(['id', 'code', 'full_name', 'email', 'lga', 'members_registered']);

                // Performance by LGA
                $performanceByLga = Enumerator::select('lga', DB::raw('COUNT(*) as enumerator_count'), DB::raw('SUM(external_members_count) as total_members'))
                    ->joinSub(
                        ExternalMember::select('agentcode', DB::raw('COUNT(*) as external_members_count'))
                            ->groupBy('agentcode'),
                        'member_counts',
                        'enumerators.code',
                        '=',
                        'member_counts.agentcode'
                    )
                    ->groupBy('lga')
                    ->orderBy('total_members', 'desc')
                    ->get();

            } catch (\Exception $relationError) {
                Log::warning('Admin: Relationship method failed, using direct connection', [
                    'error' => $relationError->getMessage()
                ]);

                // Fallback: Use direct database connection
                $enumerators = $this->getEnumeratorPerformanceDirect();
                $stats = $this->getPerformanceStatsDirect();
                $topPerformers = $this->getTopPerformersDirect();
                $performanceByLga = $this->getPerformanceByLgaDirect();
            }

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('Admin: Enumerator Performance Successful', [
                'enumerators_count' => $enumerators->count(),
                'response_time_ms' => $responseTime,
                'timestamp' => now()->toISOString()
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'enumerators' => $enumerators,
                    'stats' => $stats,
                    'top_performers' => $topPerformers,
                    'performance_by_lga' => $performanceByLga,
                ],
                'response_time_ms' => $responseTime
            ]);

        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error('Admin: Enumerator Performance Failed', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'response_time_ms' => $responseTime,
                'timestamp' => now()->toISOString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch enumerator performance data',
                'error' => app()->environment('local') ? $e->getMessage() : 'Database permission error. Contact administrator.'
            ], 500);
        }
    }

    /**
     * Get enumerator performance using direct database connection
     */
    private function getEnumeratorPerformanceDirect()
    {
        // Get enumerators
        $enumerators = Enumerator::select('id', 'code', 'full_name', 'email', 'whatsapp', 'lga', 'ward', 'registered_at')
            ->orderBy('registered_at', 'desc')
            ->get();

        // Get member counts from external database
        $memberCounts = DB::connection('external_mysql')
            ->table('members')
            ->select('agentcode', DB::raw('COUNT(*) as count'))
            ->groupBy('agentcode')
            ->pluck('count', 'agentcode');

        // Attach member counts to enumerators
        $enumerators->each(function ($enumerator) use ($memberCounts) {
            $enumerator->members_registered = $memberCounts->get($enumerator->code, 0);
        });

        // Sort by member count
        $enumerators = $enumerators->sortByDesc('members_registered')->values();

        // Manually paginate
        $page = request()->get('page', 1);
        $perPage = 50;
        $offset = ($page - 1) * $perPage;
        
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $enumerators->slice($offset, $perPage),
            $enumerators->count(),
            $perPage,
            $page
        );

        return $paginated;
    }

    /**
     * Get performance stats using direct connection
     */
    private function getPerformanceStatsDirect()
    {
        $totalEnumerators = Enumerator::count();
        $totalMembers = DB::connection('external_mysql')->table('members')->count();
        
        // Get member counts by agent code
        $memberCounts = DB::connection('external_mysql')
            ->table('members')
            ->select('agentcode', DB::raw('COUNT(*) as count'))
            ->groupBy('agentcode')
            ->pluck('count', 'agentcode');

        $enumeratorsWithMembers = 0;
        $topPerformerCount = 0;
        $topPerformer = null;

        foreach ($memberCounts as $agentCode => $count) {
            if ($count > 0) {
                $enumeratorsWithMembers++;
            }
            if ($count > $topPerformerCount) {
                $topPerformerCount = $count;
                $topPerformer = Enumerator::where('code', $agentCode)->first();
                if ($topPerformer) {
                    $topPerformer->members_registered = $count;
                }
            }
        }

        return [
            'total_enumerators' => $totalEnumerators,
            'total_members_registered' => $totalMembers,
            'enumerators_with_members' => $enumeratorsWithMembers,
            'enumerators_without_members' => $totalEnumerators - $enumeratorsWithMembers,
            'average_members_per_enumerator' => $totalMembers / max($totalEnumerators, 1),
            'top_performer' => $topPerformer,
        ];
    }

    /**
     * Get top performers using direct connection
     */
    private function getTopPerformersDirect()
    {
        // Get member counts by agent code
        $memberCounts = DB::connection('external_mysql')
            ->table('members')
            ->select('agentcode', DB::raw('COUNT(*) as count'))
            ->groupBy('agentcode')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        $topPerformers = collect();
        foreach ($memberCounts as $memberCount) {
            $enumerator = Enumerator::where('code', $memberCount->agentcode)->first();
            if ($enumerator) {
                $enumerator->members_registered = $memberCount->count;
                $topPerformers->push($enumerator);
            }
        }

        return $topPerformers;
    }

    /**
     * Get performance by LGA using direct connection
     */
    private function getPerformanceByLgaDirect()
    {
        // Get member counts by agent code
        $memberCounts = DB::connection('external_mysql')
            ->table('members')
            ->select('agentcode', DB::raw('COUNT(*) as count'))
            ->groupBy('agentcode')
            ->pluck('count', 'agentcode');

        // Group by LGA
        $lgaPerformance = Enumerator::all()->groupBy('lga')->map(function ($enumerators, $lga) use ($memberCounts) {
            $totalMembers = 0;
            foreach ($enumerators as $enumerator) {
                $totalMembers += $memberCounts->get($enumerator->code, 0);
            }

            return (object) [
                'lga' => $lga,
                'enumerator_count' => $enumerators->count(),
                'total_members' => $totalMembers,
            ];
        })->sortByDesc('total_members')->values();

        return $lgaPerformance;
    }

    /**
     * Show members registered by a specific enumerator
     */
    public function showEnumeratorMembers(Request $request, $code)
    {
        $startTime = microtime(true);
        
        Log::info('Admin: Show Enumerator Members Request Started', [
            'code' => $code,
            'ip' => $request->ip(),
            'timestamp' => now()->toISOString()
        ]);

        try {
            // Get enumerator details
            $enumerator = Enumerator::where('code', $code)->first();
            
            if (!$enumerator) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Enumerator not found'
                    ], 404);
                }
                abort(404);
            }

            // Try using relationship first
            try {
                $members = $enumerator->externalMembers()->paginate(20);
                $totalMembers = $enumerator->externalMembers()->count();
            } catch (\Exception $relationError) {
                Log::warning('Admin: Relationship method failed for enumerator members, using direct connection', [
                    'error' => $relationError->getMessage(),
                    'enumerator_code' => $code
                ]);

                // Fallback: Use direct database connection
                $members = $this->getEnumeratorMembersDirect($code);
                $totalMembers = DB::connection('external_mysql')
                    ->table('members')
                    ->where('agentcode', $code)
                    ->count();
            }

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('Admin: Show Enumerator Members Successful', [
                'enumerator_code' => $code,
                'members_count' => $members->count(),
                'total_members' => $totalMembers,
                'response_time_ms' => $responseTime,
                'timestamp' => now()->toISOString()
            ]);

            // Return JSON for API calls, Inertia for web requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'enumerator' => $enumerator,
                        'members' => $members,
                        'total_members' => $totalMembers,
                    ],
                    'response_time_ms' => $responseTime
                ]);
            }

            return Inertia::render('Admin/EnumeratorMembers', [
                'enumerator' => $enumerator,
                'members' => $members,
                'total_members' => $totalMembers,
            ]);

        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error('Admin: Show Enumerator Members Failed', [
                'enumerator_code' => $code,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'response_time_ms' => $responseTime,
                'timestamp' => now()->toISOString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch enumerator members',
                    'error' => app()->environment('local') ? $e->getMessage() : 'Database error'
                ], 500);
            }

            abort(500, 'Failed to fetch enumerator members');
        }
    }

    /**
     * Get enumerator members using direct database connection
     */
    private function getEnumeratorMembersDirect($code)
    {
        $members = DB::connection('external_mysql')
            ->table('members')
            ->where('agentcode', $code)
            ->orderBy('registration_date', 'desc')
            ->paginate(20);

        return $members;
    }

    /**
     * Fetch data from external API
     */
    private function fetchExternalData()
    {
        try {
            $dataUrl = env('DATA_URL') . '/api/data';
            $apiToken = env('DATA_API');
            
            // Validate configuration
            Log::info('Data API configuration check', [
                'url_from_env' => $dataUrl,
                'token_from_env' => $apiToken ? 'SET' : 'NOT_SET',
                'token_length' => $apiToken ? strlen($apiToken) : 0,
                'raw_env_url' => env('DATA_URL'),
                'raw_env_token' => env('DATA_API') ? 'SET' : 'NOT_SET',
                'config_url' => config('services.data_api.url'),
                'config_token' => config('services.data_api.token') ? 'SET' : 'NOT_SET',
                'timestamp' => now()->toISOString()
            ]);
            
            if (empty($dataUrl) || empty($apiToken)) {
                Log::error('Data API configuration missing', [
                    'url_configured' => !empty($dataUrl),
                    'token_configured' => !empty($apiToken),
                    'timestamp' => now()->toISOString()
                ]);
                return null;
            }
            
            $client = new \GuzzleHttp\Client([
                'timeout' => 30,
                'headers' => [
                    'accept' => '*/*',
                    'Authorization' => 'Bearer ' . $apiToken,
                    'Content-Type' => 'application/json',
                ],
            ]);

            $response = $client->get($dataUrl);
            
            if ($response->getStatusCode() === 200) {
                $responseData = json_decode($response->getBody()->getContents(), true);
                
                // Extract the data array from the response
                $dataPlans = null;
                if (isset($responseData['success']) && $responseData['success'] === true && isset($responseData['data'])) {
                    $dataPlans = $responseData['data'];
                } elseif (is_array($responseData)) {
                    $dataPlans = $responseData;
                }
                
                Log::info('External API data fetched successfully', [
                    'url' => $dataUrl,
                    'status' => $response->getStatusCode(),
                    'response_structure' => isset($responseData['success']) ? 'structured' : 'array',
                    'data_count' => is_array($dataPlans) ? count($dataPlans) : 0,
                    'timestamp' => now()->toISOString()
                ]);
                
                return $dataPlans;
            } else {
                Log::warning('External API returned non-200 status', [
                    'url' => $dataUrl,
                    'status' => $response->getStatusCode(),
                    'timestamp' => now()->toISOString()
                ]);
                return null;
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch external API data', [
                'error' => $e->getMessage(),
                'url' => $dataUrl ?? 'unknown',
                'timestamp' => now()->toISOString()
            ]);
            return null;
        }
    }

    /**
     * Show data subscription page for top performers
     */
    public function dataSub(Request $request)
    {
        $startTime = microtime(true);
        
        Log::info('Admin: Data Sub Request Started', [
            'ip' => $request->ip(),
            'timestamp' => now()->toISOString()
        ]);

        try {
            // Get top performers with more than 2 members
            $topPerformers = $this->getTopPerformersForDataSub();
            
            // Get unique networks from the data
            $networks = $topPerformers->pluck('browsing_network')->unique()->filter()->values();
            
            // Get selected network from request
            $selectedNetwork = $request->get('network', '');
            $filteredPerformers = collect();
            
            if ($selectedNetwork && $networks->contains($selectedNetwork)) {
                $filteredPerformers = $topPerformers->where('browsing_network', $selectedNetwork);
            } else {
                $filteredPerformers = $topPerformers;
            }

            // Fetch external API data
            $externalData = $this->fetchExternalData();

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('Admin: Data Sub Successful', [
                'total_top_performers' => $topPerformers->count(),
                'selected_network' => $selectedNetwork,
                'filtered_count' => $filteredPerformers->count(),
                'available_networks' => $networks->toArray(),
                'external_data_fetched' => $externalData !== null,
                'response_time_ms' => $responseTime,
                'timestamp' => now()->toISOString()
            ]);

            return Inertia::render('Admin/DataSub', [
                'topPerformers' => $topPerformers,
                'filteredPerformers' => $filteredPerformers,
                'networks' => $networks,
                'selectedNetwork' => $selectedNetwork,
                'externalData' => $externalData,
                'stats' => [
                    'total_top_performers' => $topPerformers->count(),
                    'unique_networks' => $networks->count(),
                    'filtered_count' => $filteredPerformers->count(),
                ]
            ]);

        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error('Admin: Data Sub Failed', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'response_time_ms' => $responseTime,
                'timestamp' => now()->toISOString()
            ]);

            abort(500, 'Failed to load data subscription page');
        }
    }

    /**
     * Get top performers for data subscription (enumerators with more than 2 members)
     */
    private function getTopPerformersForDataSub()
    {
        try {
            // Step 1: Get all enumerators from our main database
            $enumerators = DB::table('enumerators')
                ->select([
                    'id', 
                    'code', 
                    'full_name', 
                    'email', 
                    'whatsapp', 
                    'lga', 
                    'ward', 
                    'browsing_number', 
                    'browsing_network', 
                    'registered_at'
                ])
                ->get();
            
            Log::info('Enumerators fetched from main database', [
                'count' => $enumerators->count(),
                'timestamp' => now()->toISOString()
            ]);
            
            // Step 2: For each enumerator, count their members in the external database
            $topPerformers = $enumerators->map(function ($enumerator) {
                try {
                    $memberCount = DB::connection('external_mysql')
                        ->table('members')
                        ->where('agentcode', $enumerator->code)
                        ->count();
                    
                    $enumerator->members_registered = $memberCount;
                    return $enumerator;
                    
                } catch (\Exception $memberException) {
                    Log::warning('Failed to count members for enumerator in external database', [
                        'code' => $enumerator->code,
                        'error' => $memberException->getMessage()
                    ]);
                    $enumerator->members_registered = 0;
                    return $enumerator;
                }
            })->filter(function ($enumerator) {
                return $enumerator->members_registered > 2;
            })->sortByDesc('members_registered')->values();
            
            Log::info('Top performers calculated successfully', [
                'total_enumerators' => $enumerators->count(),
                'top_performers_count' => $topPerformers->count(),
                'timestamp' => now()->toISOString()
            ]);
            
            return $topPerformers;
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch enumerators from main database', [
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ]);
            
            // Return empty collection as last resort
            return collect([]);
        }
    }

    /**
     * Show data plan management page for multi-registrations
     */
    public function dataPlanManagement(Request $request)
    {
        $startTime = microtime(true);
        
        Log::info('Admin: Data Plan Management Request Started', [
            'ip' => $request->ip(),
            'timestamp' => now()->toISOString()
        ]);

        try {
            // Get people with more than 2 registrations (using browsing_number as identifier)
            $multiRegistrations = $this->getMultiRegistrations();
            
            // Get unique networks from the data
            $networks = $multiRegistrations->pluck('browsing_network')->unique()->filter()->values();
            
            // Get selected network from request
            $selectedNetwork = $request->get('network', '');
            $filteredRegistrations = collect();
            
            if ($selectedNetwork && $networks->contains($selectedNetwork)) {
                $filteredRegistrations = $multiRegistrations->where('browsing_network', $selectedNetwork);
            } else {
                $filteredRegistrations = $multiRegistrations;
            }

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('Admin: Data Plan Management Successful', [
                'total_multi_registrations' => $multiRegistrations->count(),
                'selected_network' => $selectedNetwork,
                'filtered_count' => $filteredRegistrations->count(),
                'available_networks' => $networks->toArray(),
                'response_time_ms' => $responseTime,
                'timestamp' => now()->toISOString()
            ]);

            return Inertia::render('Admin/DataPlanManagement', [
                'multiRegistrations' => $multiRegistrations,
                'filteredRegistrations' => $filteredRegistrations,
                'networks' => $networks,
                'selectedNetwork' => $selectedNetwork,
                'stats' => [
                    'total_multi_registrations' => $multiRegistrations->count(),
                    'unique_networks' => $networks->count(),
                    'filtered_count' => $filteredRegistrations->count(),
                ]
            ]);

        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error('Admin: Data Plan Management Failed', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'response_time_ms' => $responseTime,
                'timestamp' => now()->toISOString()
            ]);

            abort(500, 'Failed to load data plan management page');
        }
    }

    /**
     * Get people with more than 2 registrations
     */
    private function getMultiRegistrations()
    {
        try {
            // Use relationship first if permissions allow
            try {
                $multiRegistrations = Enumerator::select('browsing_network', 'browsing_number')
                    ->selectRaw('COUNT(*) as registration_count')
                    ->selectRaw('GROUP_CONCAT(full_name) as names')
                    ->selectRaw('GROUP_CONCAT(email) as emails')
                    ->selectRaw('GROUP_CONCAT(whatsapp) as whatsapp_numbers')
                    ->selectRaw('GROUP_CONCAT(code) as codes')
                    ->selectRaw('MIN(registered_at) as first_registration')
                    ->selectRaw('MAX(registered_at) as last_registration')
                    ->whereNotNull('browsing_number')
                    ->where('browsing_number', '!=', '')
                    ->groupBy('browsing_number', 'browsing_network')
                    ->having('registration_count', '>', 2)
                    ->orderBy('registration_count', 'desc')
                    ->get();
            } catch (\Exception $relationError) {
                Log::warning('Admin: Using direct connection for multi-registrations', [
                    'error' => $relationError->getMessage()
                ]);

                // Fallback: Use direct database connection
                $multiRegistrations = DB::table('enumerators')
                    ->select('browsing_network', 'browsing_number')
                    ->selectRaw('COUNT(*) as registration_count')
                    ->selectRaw('GROUP_CONCAT(full_name) as names')
                    ->selectRaw('GROUP_CONCAT(email) as emails')
                    ->selectRaw('GROUP_CONCAT(whatsapp) as whatsapp_numbers')
                    ->selectRaw('GROUP_CONCAT(code) as codes')
                    ->selectRaw('MIN(registered_at) as first_registration')
                    ->selectRaw('MAX(registered_at) as last_registration')
                    ->whereNotNull('browsing_number')
                    ->where('browsing_number', '!=', '')
                    ->groupBy('browsing_number', 'browsing_network')
                    ->having('registration_count', '>', 2)
                    ->orderBy('registration_count', 'desc')
                    ->get();
            }

            // Transform the data for better UI display
            return $multiRegistrations->map(function ($item) {
                return [
                    'browsing_number' => $item->browsing_number,
                    'browsing_network' => $item->browsing_network,
                    'registration_count' => (int) $item->registration_count,
                    'names' => explode(',', $item->names),
                    'emails' => explode(',', $item->emails),
                    'whatsapp_numbers' => explode(',', $item->whatsapp_numbers),
                    'codes' => explode(',', $item->codes),
                    'first_registration' => $item->first_registration,
                    'last_registration' => $item->last_registration,
                    'selected' => false,
                ];
            });

        } catch (\Exception $e) {
            Log::error('Admin: Failed to get multi-registrations', [
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    public function enumerators(Request $request)
    {
        $query = Enumerator::query();

        // Search functionality
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('full_name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%')
                  ->orWhere('whatsapp', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by LGA
        if ($request->lga) {
            $query->where('lga', $request->lga);
        }

        // Filter by date range
        if ($request->date_from) {
            $query->whereDate('registered_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('registered_at', '<=', $request->date_to);
        }

        $enumerators = $query->latest('registered_at')
            ->paginate(50)
            ->withQueryString();

        $lgas = Enumerator::distinct('lga')->pluck('lga')->sort()->toArray();

        return Inertia::render('Admin/Enumerators', [
            'enumerators' => $enumerators,
            'lgas' => $lgas,
            'filters' => $request->only(['search', 'lga', 'date_from', 'date_to']),
        ]);
    }

    public function showEnumerator(Enumerator $enumerator)
    {
        return Inertia::render('Admin/EnumeratorDetails', [
            'enumerator' => $enumerator,
        ]);
    }
}
