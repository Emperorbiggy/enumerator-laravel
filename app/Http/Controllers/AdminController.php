<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\DataSubscription;
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
            
            // Apply eligibility checking to hide recently skipped performers
            $eligiblePerformers = [];
            $skippedPerformers = [];

            foreach ($topPerformers as $performer) {
                try {
                    // Get member counts from external database only
                    try {
                        $currentRegisteredCount = DB::connection('external_mysql')
                            ->table('members')
                            ->where('agentcode', $performer->code)
                            ->count();
                        $dataSource = 'external';
                    } catch (\Exception $memberException) {
                        Log::error('External database not reachable', [
                            'performer_id' => $performer->id,
                            'error' => $memberException->getMessage()
                        ]);

                        // Skip this performer - no fallback for production
                        $skippedPerformers[] = [
                            'performer_id' => $performer->id,
                            'phone' => $performer->browsing_number ?? 'Unknown',
                            'reason' => 'external_database_unreachable',
                            'error' => $memberException->getMessage()
                        ];
                        continue;
                    }

                    // Check if performer has any previous subscription (not just recent)
                    $firstSubscription = DB::table('data_subscriptions')
                        ->where('enumerator_id', $performer->id)
                        ->where('status', 'success')
                        ->orderBy('created_at', 'asc')
                        ->first();

                    if ($firstSubscription) {
                        // Calculate target: initial registered count + 100
                        $initialRegisteredCount = $firstSubscription->registered_users_count ?? 0;
                        $targetCount = $initialRegisteredCount + 100;

                        if ($currentRegisteredCount >= $targetCount) {
                            // Eligible for new data subscription
                            $performer->current_registered_count = $currentRegisteredCount;
                            $performer->initial_registered_count = $initialRegisteredCount;
                            $performer->target_count = $targetCount;
                            $performer->growth = $currentRegisteredCount - $initialRegisteredCount;
                            $performer->data_source = $dataSource;
                            $eligiblePerformers[] = $performer;
                        } else {
                            // Not enough growth to reach target - skip from display
                            $skippedPerformers[] = [
                                'performer_id' => $performer->id,
                                'phone' => $performer->browsing_number,
                                'reason' => 'below_target_count',
                                'initial_count' => $initialRegisteredCount,
                                'current_count' => $currentRegisteredCount,
                                'target_count' => $targetCount,
                                'growth_needed' => $targetCount - $currentRegisteredCount
                            ];
                        }
                    } else {
                        // No previous subscription - eligible for first time
                        $performer->current_registered_count = $currentRegisteredCount;
                        $performer->initial_registered_count = $currentRegisteredCount;
                        $performer->target_count = $currentRegisteredCount + 100;
                        $performer->growth = 0;
                        $performer->data_source = $dataSource;
                        $eligiblePerformers[] = $performer;
                    }

                } catch (\Exception $e) {
                    // Error processing this performer - skip
                    $skippedPerformers[] = [
                        'performer_id' => $performer->id,
                        'phone' => $performer->browsing_number ?? 'Unknown',
                        'reason' => 'processing_error',
                        'error' => $e->getMessage()
                    ];
                }
            }

            Log::info('Data Sub eligibility check completed', [
                'total_performers' => $topPerformers->count(),
                'eligible_count' => count($eligiblePerformers),
                'skipped_count' => count($skippedPerformers)
            ]);

            // Use only eligible performers for display
            $displayPerformers = collect($eligiblePerformers);
            
            // Get unique networks from the data (case-insensitive)
            $networks = $displayPerformers->pluck('browsing_network')
                ->map(fn($network) => strtolower($network ?? ''))
                ->unique()
                ->filter()
                ->values();
            
            // Get selected network from request (convert to lowercase)
            $selectedNetwork = strtolower($request->get('network', ''));
            $filteredPerformers = collect();
            
            if ($selectedNetwork && $networks->contains($selectedNetwork)) {
                $filteredPerformers = $displayPerformers->filter(function($performer) use ($selectedNetwork) {
                    return strtolower($performer->browsing_network ?? '') === $selectedNetwork;
                });
            } else {
                $filteredPerformers = $displayPerformers;
            }

            // Fetch external API data
            $externalData = $this->fetchExternalData();

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('Admin: Data Sub Successful', [
                'total_performers' => $topPerformers->count(),
                'eligible_performers' => $displayPerformers->count(),
                'skipped_performers' => count($skippedPerformers),
                'selected_network' => $selectedNetwork,
                'filtered_count' => $filteredPerformers->count(),
                'available_networks' => $networks->toArray(),
                'external_data_fetched' => $externalData !== null,
                'response_time_ms' => $responseTime,
                'timestamp' => now()->toISOString()
            ]);

            return Inertia::render('Admin/DataSub', [
                'topPerformers' => $displayPerformers,
                'filteredPerformers' => $filteredPerformers,
                'networks' => $networks,
                'selectedNetwork' => $selectedNetwork,
                'externalData' => $externalData,
                'stats' => [
                    'total_top_performers' => $topPerformers->count(),
                    'eligible_performers' => $displayPerformers->count(),
                    'skipped_performers' => count($skippedPerformers),
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
     * Get all enumerators with their member counts for data subscription
     */
    private function getTopPerformersForDataSub()
    {
        try {
            $enumerators = DB::table('enumerators')
                ->select([
                    'id',
                    'full_name',
                    'code',
                    'email',
                    'browsing_number',
                    'whatsapp',
                    'account_number',
                    'bank_name',
                    'browsing_network',
                    'state',
                    'lga',
                    'ward'
                ])
                ->get();

            // Try to get member counts from external database
            $enumeratorsWithCounts = $enumerators->map(function ($enumerator) {
                try {
                    $memberCount = DB::connection('external_mysql')
                        ->table('members')
                        ->where('agentcode', $enumerator->code)
                        ->count();

                    $enumerator->members_registered = $memberCount;
                    $enumerator->data_source = 'external';
                    return $enumerator;

                } catch (\Exception $memberException) {
                    Log::error('External database not reachable', [
                        'enumerator_id' => $enumerator->id,
                        'error' => $memberException->getMessage()
                    ]);

                    // Skip this enumerator - no fallback for production
                    $enumerator->members_registered = 0;
                    $enumerator->data_source = 'error';
                    return $enumerator;
                }
            })->sortByDesc('members_registered')->values();

            // Filter to only return those with 2 or more registered users
            $topPerformers = $enumeratorsWithCounts->filter(function ($enumerator) {
                return $enumerator->members_registered >= 2;
            })->values();

            return $topPerformers;

        } catch (\Exception $e) {
            Log::error('Failed to get enumerators for data sub', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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
            
            // Get unique networks from the data (case-insensitive)
            $networks = $multiRegistrations->pluck('browsing_network')
                ->map(fn($network) => strtolower($network ?? ''))
                ->unique()
                ->filter()
                ->values();
            
            // Get selected network from request (convert to lowercase)
            $selectedNetwork = strtolower($request->get('network', ''));
            $filteredRegistrations = collect();
            
            if ($selectedNetwork && $networks->contains($selectedNetwork)) {
                $filteredRegistrations = $multiRegistrations->filter(function($registration) use ($selectedNetwork) {
                    return strtolower($registration->browsing_network ?? '') === $selectedNetwork;
                });
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

    /**
     * Send batch data to selected enumerators
     */
    public function sendBatchData(Request $request)
    {
        // Increase execution time for large batches
        set_time_limit(600); // 10 minutes
        
        $startTime = microtime(true);
        
        try {
            $validated = $request->validate([
                'performer_ids' => 'required|array',
                'performer_ids.*' => 'integer|exists:enumerators,id',
                'plan_code' => 'required|string',
                'network' => 'required|string',
            ]);

            $performerIds = $validated['performer_ids'];
            $planCode = $validated['plan_code'];
            $network = $validated['network'];

            // Limit batch size to prevent timeout (max 50 per batch)
            if (count($performerIds) > 50) {
                return response()->json([
                    'success' => false,
                    'message' => 'Batch size too large. Maximum 50 performers allowed per batch to prevent timeout.',
                    'requested_count' => count($performerIds),
                    'max_allowed' => 50
                ], 400);
            }

            Log::info('Batch data send started', [
                'performer_count' => count($performerIds),
                'plan_code' => $planCode,
                'network' => $network,
                'admin_id' => Auth::guard('admin')->id(),
                'estimated_time_minutes' => round((count($performerIds) * 10.5) / 60, 1), // ~10.5 seconds per performer (10s delay + processing)
                'timestamp' => now()->toISOString()
            ]);

            // Get selected performers
            $performers = Enumerator::whereIn('id', $performerIds)->get();
            
            if ($performers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid performers found'
                ], 400);
            }

            // Initialize API client
            $apiUrl = env('DATA_URL') . '/api/data';
            $apiToken = env('DATA_API');
            
            if (empty($apiUrl) || empty($apiToken)) {
                Log::error('API configuration missing for batch send');
                return response()->json([
                    'success' => false,
                    'message' => 'API configuration missing'
                ], 500);
            }

            $client = new \GuzzleHttp\Client([
                'timeout' => 30,
                'headers' => [
                    'accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $apiToken,
                    'Content-Type' => 'application/json',
                ],
            ]);

            // Filter performers based on recent subscription and growth criteria
            $eligiblePerformers = [];
            $skippedPerformers = [];

            foreach ($performers as $performer) {
                try {
                    // Get member counts from external database only
                    try {
                        $currentRegisteredCount = DB::connection('external_mysql')
                            ->table('members')
                            ->where('agentcode', $performer->code)
                            ->count();
                        $dataSource = 'external';
                    } catch (\Exception $memberException) {
                        Log::error('External database not reachable', [
                            'performer_id' => $performer->id,
                            'error' => $memberException->getMessage()
                        ]);

                        // Skip this performer - no fallback for production
                        $skippedPerformers[] = [
                            'performer_id' => $performer->id,
                            'phone' => $performer->browsing_number ?? 'Unknown',
                            'reason' => 'external_database_unreachable',
                            'error' => $memberException->getMessage()
                        ];

                        Log::error('Performer skipped - external database unreachable', [
                            'performer_id' => $performer->id,
                            'phone' => $performer->browsing_number,
                            'error' => $memberException->getMessage()
                        ]);
                        continue; // Skip to next performer
                    }

                    // Check if performer has any previous subscription (not just recent)
                    $firstSubscription = DB::table('data_subscriptions')
                        ->where('enumerator_id', $performer->id)
                        ->where('status', 'success')
                        ->orderBy('created_at', 'asc')
                        ->first();

                    if ($firstSubscription) {
                        // Calculate target: initial registered count + 100
                        $initialRegisteredCount = $firstSubscription->registered_users_count ?? 0;
                        $targetCount = $initialRegisteredCount + 100;

                        if ($currentRegisteredCount >= $targetCount) {
                            // Eligible for new data subscription
                            $performer->current_registered_count = $currentRegisteredCount;
                            $performer->initial_registered_count = $initialRegisteredCount;
                            $performer->target_count = $targetCount;
                            $performer->growth = $currentRegisteredCount - $initialRegisteredCount;
                            $performer->data_source = $dataSource;
                            $eligiblePerformers[] = $performer;

                            Log::info('Performer eligible for data subscription', [
                                'performer_id' => $performer->id,
                                'phone' => $performer->browsing_number,
                                'initial_count' => $initialRegisteredCount,
                                'current_count' => $currentRegisteredCount,
                                'target_count' => $targetCount,
                                'growth' => $currentRegisteredCount - $initialRegisteredCount,
                                'first_subscription' => $firstSubscription->created_at
                            ]);
                        } else {
                            // Not enough growth to reach target - skip
                            $skippedPerformers[] = [
                                'performer_id' => $performer->id,
                                'phone' => $performer->browsing_number,
                                'reason' => 'below_target_count',
                                'initial_count' => $initialRegisteredCount,
                                'current_count' => $currentRegisteredCount,
                                'target_count' => $targetCount,
                                'growth_needed' => $targetCount - $currentRegisteredCount
                            ];

                            Log::info('Performer skipped - below target count', [
                                'performer_id' => $performer->id,
                                'phone' => $performer->browsing_number,
                                'initial_count' => $initialRegisteredCount,
                                'current_count' => $currentRegisteredCount,
                                'target_count' => $targetCount,
                                'growth_needed' => $targetCount - $currentRegisteredCount
                            ]);
                        }
                    } else {
                        // No previous subscription - eligible for first time
                        $performer->current_registered_count = $currentRegisteredCount;
                        $performer->initial_registered_count = $currentRegisteredCount;
                        $performer->target_count = $currentRegisteredCount + 100;
                        $performer->growth = 0;
                        $performer->data_source = $dataSource;
                        $eligiblePerformers[] = $performer;

                        Log::info('Performer eligible - first time subscription', [
                            'performer_id' => $performer->id,
                            'phone' => $performer->browsing_number,
                            'current_count' => $currentRegisteredCount,
                            'future_target' => $currentRegisteredCount + 100,
                            'first_time' => true
                        ]);
                    }

                } catch (\Exception $e) {
                    // Error processing this performer - skip
                    $skippedPerformers[] = [
                        'performer_id' => $performer->id,
                        'phone' => $performer->browsing_number ?? 'Unknown',
                        'reason' => 'processing_error',
                        'error' => $e->getMessage()
                    ];

                    Log::error('Error processing performer eligibility', [
                        'performer_id' => $performer->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Batch eligibility check completed', [
                'total_requested' => $performers->count(),
                'eligible_count' => count($eligiblePerformers),
                'skipped_count' => count($skippedPerformers),
                'plan_code' => $planCode,
                'network' => $network
            ]);

            // If no eligible performers, return early
            if (empty($eligiblePerformers)) {
                return response()->json([
                    'success' => true,
                    'message' => 'No eligible performers found for data subscription. All performers were skipped based on criteria.',
                    'results' => [
                        'eligible' => [],
                        'skipped' => $skippedPerformers
                    ],
                    'summary' => [
                        'total_requested' => $performers->count(),
                        'eligible' => 0,
                        'skipped' => count($skippedPerformers),
                    ]
                ]);
            }

            // Process only eligible performers
            $results = [];
            $successCount = 0;
            $failedCount = 0;
            $processedCount = 0;

            foreach ($eligiblePerformers as $index => $performer) {
                try {
                    // Check if we're approaching timeout
                    $elapsedTime = microtime(true) - $startTime;
                    $remainingTime = 600 - $elapsedTime; // 10 minutes limit
                    $estimatedRemaining = (count($eligiblePerformers) - $index) * 10.5; // ~10.5 seconds per remaining performer
                    
                    if ($estimatedRemaining > $remainingTime) {
                        Log::warning('Approaching timeout, stopping early', [
                            'processed' => $processedCount,
                            'remaining' => count($eligiblePerformers) - $index,
                            'elapsed_seconds' => round($elapsedTime),
                            'estimated_remaining_seconds' => round($estimatedRemaining),
                            'time_limit_seconds' => 600
                        ]);
                        
                        // Return partial results
                        return response()->json([
                            'success' => true,
                            'message' => "Partial completion due to time limit. Processed {$processedCount} of " . count($eligiblePerformers) . " eligible performers.",
                            'results' => [
                                'eligible' => $results,
                                'skipped' => array_merge($skippedPerformers, [
                                    [
                                        'reason' => 'timeout_prevention',
                                        'message' => 'Stopped early to prevent PHP timeout',
                                        'processed_count' => $processedCount,
                                        'remaining_count' => count($eligiblePerformers) - $index
                                    ]
                                ])
                            ],
                            'summary' => [
                                'total_requested' => $performers->count(),
                                'eligible' => count($eligiblePerformers),
                                'skipped' => count($skippedPerformers),
                                'success' => $successCount,
                                'failed' => $failedCount,
                                'timeout_prevention' => true
                            ]
                        ]);
                    }
                    
                    $phone = $this->normalizePhoneNumber($performer->browsing_number);
                    
                    if (empty($phone) || strlen($phone) !== 11) {
                        Log::warning('Performer has invalid phone number after normalization', [
                            'performer_id' => $performer->id,
                            'original_phone' => $performer->browsing_number,
                            'normalized_phone' => $phone,
                            'code' => $performer->code
                        ]);
                        continue;
                    }

                    // Add delay to respect API rate limits (10 seconds between calls)
                    if ($results !== []) {
                        Log::info('Delaying 10 seconds to respect API rate limit');
                        sleep(10);
                    }

                    // Validate API request data before sending
                    $apiRequestData = [
                        'plan_code' => $planCode,
                        'phone' => $phone
                    ];

                    Log::info('Making API call', [
                        'performer_id' => $performer->id,
                        'phone' => $phone,
                        'original_phone' => $performer->browsing_number,
                        'plan_code' => $planCode,
                        'network' => $network,
                        'api_url' => $apiUrl,
                        'request_data' => $apiRequestData
                    ]);

                    // Make API call
                    $response = $client->post($apiUrl, [
                        'json' => $apiRequestData,
                        'timeout' => 30,
                        'connect_timeout' => 10
                    ]);

                    $responseBody = $response->getBody()->getContents();
                    $responseData = json_decode($responseBody, true);

                    // Validate API response
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new \Exception('Invalid JSON response from API: ' . json_last_error_msg());
                    }

                    Log::info('API response received', [
                        'performer_id' => $performer->id,
                        'phone' => $phone,
                        'response_success' => $responseData['success'] ?? false,
                        'response_message' => $responseData['message'] ?? 'No message',
                        'http_status' => $response->getStatusCode()
                    ]);

                    // Create subscription record
                    $subscription = new DataSubscription();
                    $subscription->transaction_id = $responseData['data']['transactionId'] ?? uniqid('txn_');
                    $subscription->phone = $phone; // Use normalized phone number
                    $subscription->plan_code = $planCode;
                    $subscription->plan_name = $responseData['data']['planName'] ?? 'Unknown';
                    $subscription->network = $responseData['data']['network'] ?? $network;
                    $subscription->plan_type = $responseData['data']['planType'] ?? 'Unknown';
                    $subscription->amount = $this->cleanAmount($responseData['data']['amount'] ?? 0);
                    $subscription->balance_before = $this->cleanAmount($responseData['data']['balanceBefore'] ?? 0);
                    $subscription->balance_after = $this->cleanAmount($responseData['data']['balanceAfter'] ?? 0);
                    $subscription->response_message = $responseData['data']['response'] ?? 'Success';
                    $subscription->status = $responseData['success'] ? 'success' : 'failed';
                    $subscription->full_response = $responseData;
                    $subscription->enumerator_id = $performer->id;
                    $subscription->registered_users_count = $performer->current_registered_count;
                    $subscription->data_source = $performer->data_source;
                    $subscription->admin_id = Auth::guard('admin')->id();
                    
                    try {
                        $subscription->save();
                    } catch (\Exception $saveException) {
                        Log::warning('Failed to save subscription to database', [
                            'error' => $saveException->getMessage(),
                            'transaction_id' => $subscription->transaction_id
                        ]);
                        // Continue processing even if save fails
                    }

                    if ($responseData['success']) {
                        $successCount++;
                        Log::info('Data sent successfully', [
                            'performer_id' => $performer->id,
                            'phone' => $phone,
                            'transaction_id' => $subscription->transaction_id
                        ]);
                    } else {
                        $failedCount++;
                        Log::warning('Data send failed', [
                            'performer_id' => $performer->id,
                            'phone' => $phone,
                            'response' => $responseData
                        ]);
                    }

                    $results[] = [
                        'performer_id' => $performer->id,
                        'phone' => $phone,
                        'original_phone' => $performer->browsing_number,
                        'success' => $responseData['success'],
                        'message' => $responseData['message'] ?? 'Unknown error',
                        'transaction_id' => $subscription->transaction_id,
                        'registered_users_count' => $performer->current_registered_count,
                        'initial_registered_count' => $performer->initial_registered_count,
                        'target_count' => $performer->target_count,
                        'growth' => $performer->growth,
                        'data_source' => $performer->data_source
                    ];
                    
                    $processedCount++;

                } catch (\GuzzleHttp\Exception\ClientException $e) {
                    $failedCount++;
                    
                    // Get detailed error response
                    $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : '';
                    $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
                    $isRateLimit = strpos($responseBody, 'Please wait for') !== false;
                    
                    // Parse error response for more details
                    $errorDetails = [];
                    if (!empty($responseBody)) {
                        try {
                            $errorData = json_decode($responseBody, true);
                            $errorDetails = [
                                'success' => $errorData['success'] ?? false,
                                'message' => $errorData['message'] ?? 'Unknown error',
                                'data' => $errorData['data'] ?? [],
                                'status_code' => $statusCode
                            ];
                        } catch (\Exception $jsonException) {
                            $errorDetails = [
                                'raw_response' => $responseBody,
                                'status_code' => $statusCode,
                                'parse_error' => $jsonException->getMessage()
                            ];
                        }
                    }
                    
                    Log::error('Failed to send data to performer', [
                        'performer_id' => $performer->id,
                        'phone' => $phone,
                        'original_phone' => $performer->browsing_number,
                        'plan_code' => $planCode,
                        'network' => $network,
                        'status_code' => $statusCode,
                        'error' => $e->getMessage(),
                        'error_details' => $errorDetails,
                        'rate_limit' => $isRateLimit
                    ]);
                    
                    if ($isRateLimit) {
                        Log::warning('Rate limit encountered, waiting and retrying', [
                            'performer_id' => $performer->id,
                            'phone' => $performer->browsing_number,
                            'error' => $e->getMessage()
                        ]);
                        
                        // Wait 10 seconds and retry once
                        sleep(10);
                        
                        try {
                            $retryResponse = $client->post($apiUrl, [
                                'json' => [
                                    'plan_code' => $planCode,
                                    'phone' => $phone
                                ]
                            ]);
                            
                            $retryData = json_decode($retryResponse->getBody()->getContents(), true);
                            
                            if ($retryData['success']) {
                                // Create successful subscription record
                                $subscription = new DataSubscription();
                                $subscription->transaction_id = $retryData['data']['transactionId'] ?? uniqid('txn_');
                                $subscription->phone = $phone;
                                $subscription->plan_code = $planCode;
                                $subscription->plan_name = $retryData['data']['planName'] ?? 'Unknown';
                                $subscription->network = $retryData['data']['network'] ?? $network;
                                $subscription->plan_type = $retryData['data']['planType'] ?? 'Unknown';
                                $subscription->amount = $this->cleanAmount($retryData['data']['amount'] ?? 0);
                                $subscription->balance_before = $this->cleanAmount($retryData['data']['balanceBefore'] ?? 0);
                                $subscription->balance_after = $this->cleanAmount($retryData['data']['balanceAfter'] ?? 0);
                                $subscription->response_message = $retryData['data']['response'] ?? 'Success (after retry)';
                                $subscription->status = 'success';
                                $subscription->full_response = $retryData;
                                $subscription->enumerator_id = $performer->id;
                                $subscription->registered_users_count = $performer->members_registered ?? 0;
                                $subscription->data_source = $performer->data_source ?? 'unknown';
                                $subscription->admin_id = Auth::guard('admin')->id();
                                
                                try {
                                    $subscription->save();
                                } catch (\Exception $saveException) {
                                    Log::warning('Failed to save retry subscription to database', [
                                        'error' => $saveException->getMessage(),
                                        'transaction_id' => $subscription->transaction_id
                                    ]);
                                }
                                
                                $successCount++;
                                Log::info('Data sent successfully after retry', [
                                    'performer_id' => $performer->id,
                                    'phone' => $phone,
                                    'transaction_id' => $subscription->transaction_id
                                ]);
                                
                                $results[] = [
                                    'performer_id' => $performer->id,
                                    'phone' => $phone,
                                    'success' => true,
                                    'message' => $retryData['message'] ?? 'Success after retry',
                                    'transaction_id' => $subscription->transaction_id,
                                ];
                                
                                continue; // Skip the failed record creation
                            }
                        } catch (\Exception $retryException) {
                            Log::error('Retry also failed', [
                                'performer_id' => $performer->id,
                                'phone' => $phone,
                                'error' => $retryException->getMessage()
                            ]);
                        }
                    }
                    
                    // Create failed record (original error or retry failed)
                    $failedSubscription = new DataSubscription();
                    $failedSubscription->transaction_id = uniqid('failed_');
                    $failedSubscription->phone = $performer->browsing_number ?? 'Unknown';
                    $failedSubscription->plan_code = $planCode;
                    $failedSubscription->plan_name = 'Unknown';
                    $failedSubscription->network = $network;
                    $failedSubscription->plan_type = 'Unknown';
                    $failedSubscription->amount = 0;
                    $failedSubscription->balance_before = 0;
                    $failedSubscription->balance_after = 0;
                    $failedSubscription->response_message = $isRateLimit ? 'Rate limit exceeded' : $e->getMessage();
                    $failedSubscription->status = 'failed';
                    $failedSubscription->full_response = ['error' => $e->getMessage(), 'rate_limit' => $isRateLimit];
                    $failedSubscription->enumerator_id = $performer->id;
                    $failedSubscription->admin_id = Auth::guard('admin')->id();
                    
                    try {
                        $failedSubscription->save();
                    } catch (\Exception $saveException) {
                        Log::warning('Failed to save failed subscription to database', [
                            'error' => $saveException->getMessage(),
                            'transaction_id' => $failedSubscription->transaction_id
                        ]);
                    }

                    Log::error('Failed to send data to performer', [
                        'performer_id' => $performer->id,
                        'phone' => $performer->browsing_number,
                        'error' => $e->getMessage(),
                        'rate_limit' => $isRateLimit
                    ]);

                    $results[] = [
                        'performer_id' => $performer->id,
                        'phone' => $performer->browsing_number ?? 'Unknown',
                        'success' => false,
                        'message' => $isRateLimit ? 'Rate limit exceeded - please try again' : $e->getMessage(),
                        'transaction_id' => null,
                    ];
                }
            }

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('Batch data send completed', [
                'total_requested' => $performers->count(),
                'eligible_count' => count($eligiblePerformers),
                'skipped_count' => count($skippedPerformers),
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'response_time_ms' => $responseTime,
                'timestamp' => now()->toISOString()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Batch processing completed. Success: {$successCount}, Failed: {$failedCount}, Skipped: " . count($skippedPerformers),
                'results' => [
                    'eligible' => $results,
                    'skipped' => $skippedPerformers
                ],
                'summary' => [
                    'total_requested' => $performers->count(),
                    'eligible' => count($eligiblePerformers),
                    'skipped' => count($skippedPerformers),
                    'success' => $successCount,
                    'failed' => $failedCount,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Batch data send failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toISOString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Batch processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clean amount values by removing commas and converting to decimal
     */
    private function cleanAmount($amount)
    {
        if (is_string($amount)) {
            // Remove commas and convert to float
            return (float) str_replace(',', '', $amount);
        }
        
        return (float) $amount;
    }

    /**
     * Normalize phone number to 11-digit format
     */
    private function normalizePhoneNumber($phone)
    {
        if (empty($phone)) {
            return $phone;
        }

        // Remove all non-digit characters first
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Handle different formats
        if (strlen($phone) === 13 && strpos($phone, '234') === 0) {
            // +2349139986596 or 2349139986596 → 09139986596
            return '0' . substr($phone, 3);
        } elseif (strlen($phone) === 11 && strpos($phone, '0') === 0) {
            // Already in correct format (09139986596)
            return $phone;
        } elseif (strlen($phone) === 10) {
            // 9139986596 → 09139986596 (add leading 0)
            return '0' . $phone;
        }

        // Return original if format doesn't match expected patterns
        return $phone;
    }

    /**
     * Show data subscription transactions page
     */
    public function dataSubTransactions(Request $request)
    {
        $startTime = microtime(true);
        
        try {
            // Get pagination parameters
            $page = $request->get('page', 1);
            $perPage = 100;
            $search = $request->get('search', '');
            $status = $request->get('status', '');
            $network = $request->get('network', '');
            $dateFrom = $request->get('date_from', '');
            $dateTo = $request->get('date_to', '');

            // Build query
            $query = DB::table('data_subscriptions')
                ->select([
                    'data_subscriptions.*',
                    'enumerators.full_name as enumerator_name',
                    'enumerators.email as enumerator_email',
                    'enumerators.code as enumerator_code',
                    'enumerators.browsing_number as enumerator_phone',
                    'admins.name as admin_name'
                ])
                ->leftJoin('enumerators', 'data_subscriptions.enumerator_id', '=', 'enumerators.id')
                ->leftJoin('admins', 'data_subscriptions.admin_id', '=', 'admins.id');

            // Apply filters
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('data_subscriptions.phone', 'like', "%{$search}%")
                      ->orWhere('data_subscriptions.transaction_id', 'like', "%{$search}%")
                      ->orWhere('enumerators.full_name', 'like', "%{$search}%")
                      ->orWhere('enumerators.email', 'like', "%{$search}%")
                      ->orWhere('enumerators.code', 'like', "%{$search}%");
                });
            }

            if (!empty($status)) {
                $query->where('data_subscriptions.status', $status);
            }

            if (!empty($network)) {
                $query->where('data_subscriptions.network', $network);
            }

            if (!empty($dateFrom)) {
                $query->whereDate('data_subscriptions.created_at', '>=', $dateFrom);
            }

            if (!empty($dateTo)) {
                $query->whereDate('data_subscriptions.created_at', '<=', $dateTo);
            }

            // Get total counts
            $totalCount = $query->count();
            $successCount = (clone $query)->where('data_subscriptions.status', 'success')->count();
            $failedCount = (clone $query)->where('data_subscriptions.status', 'failed')->count();
            $pendingCount = (clone $query)->where('data_subscriptions.status', 'pending')->count();

            // Get unique networks for filter
            $networks = DB::table('data_subscriptions')
                ->whereNotNull('network')
                ->where('network', '!=', '')
                ->distinct()
                ->pluck('network')
                ->sort()
                ->values();

            // Get paginated results
            $transactions = $query->orderBy('data_subscriptions.created_at', 'desc')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get()
                ->map(function ($transaction) {
                    // Parse full_response if it's a JSON string
                    if (is_string($transaction->full_response)) {
                        try {
                            $transaction->full_response = json_decode($transaction->full_response, true);
                        } catch (\Exception $e) {
                            $transaction->full_response = ['error' => 'Invalid JSON'];
                        }
                    }
                    
                    // Add status badge class
                    $transaction->status_class = match($transaction->status) {
                        'success' => 'bg-green-100 text-green-800',
                        'failed' => 'bg-red-100 text-red-800',
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        default => 'bg-gray-100 text-gray-800'
                    };
                    
                    return $transaction;
                });

            // Calculate pagination info
            $lastPage = ceil($totalCount / $perPage);
            $from = ($page - 1) * $perPage + 1;
            $to = min($page * $perPage, $totalCount);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('Admin: Data Sub Transactions Request Completed', [
                'page' => $page,
                'per_page' => $perPage,
                'total_count' => $totalCount,
                'filtered_count' => $transactions->count(),
                'response_time_ms' => $responseTime,
                'timestamp' => now()->toISOString()
            ]);

            return Inertia::render('Admin/DataSubTransactions', [
                'transactions' => $transactions,
                'pagination' => [
                    'current_page' => $page,
                    'last_page' => $lastPage,
                    'per_page' => $perPage,
                    'from' => $from,
                    'to' => $to,
                    'total' => $totalCount,
                    'has_more' => $page < $lastPage,
                    'has_previous' => $page > 1,
                ],
                'stats' => [
                    'total_count' => $totalCount,
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                    'pending_count' => $pendingCount,
                ],
                'filters' => [
                    'search' => $search,
                    'status' => $status,
                    'network' => $network,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ],
                'available_networks' => $networks,
            ]);

        } catch (\Exception $e) {
            Log::error('Admin: Data Sub Transactions Request Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toISOString()
            ]);

            return Inertia::render('Admin/DataSubTransactions', [
                'transactions' => collect([]),
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 100,
                    'from' => 0,
                    'to' => 0,
                    'total' => 0,
                    'has_more' => false,
                    'has_previous' => false,
                ],
                'stats' => [
                    'total_count' => 0,
                    'success_count' => 0,
                    'failed_count' => 0,
                    'pending_count' => 0,
                ],
                'filters' => [
                    'search' => '',
                    'status' => '',
                    'network' => '',
                    'date_from' => '',
                    'date_to' => '',
                ],
                'available_networks' => collect([]),
                'error' => 'Failed to load transactions: ' . $e->getMessage()
            ]);
        }
    }
}
