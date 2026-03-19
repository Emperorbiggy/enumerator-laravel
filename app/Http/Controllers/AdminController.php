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
        Auth::guard('admin')->logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('admin.login');
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
                'error' => app()->environment('local') ? $e->getMessage() : 'Database error'
            ], 500);
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
