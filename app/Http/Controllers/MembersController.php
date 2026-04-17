<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\LGA;
use App\Models\Ward;
use App\Models\PollingUnit;
use App\Services\NINService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use Inertia\Inertia;

class MembersController extends Controller
{
    protected $ninService;

    public function __construct(NINService $ninService)
    {
        $this->ninService = $ninService;
    }

    /**
     * Show upload form with LGA selection
     */
    public function showUploadForm()
    {
        $lgas = LGA::orderBy('name')->get();
        return inertia('Upload', [
            'lgas' => $lgas
        ]);
    }

    /**
     * Process uploaded CSV/Excel file and verify NINs
     */
    public function uploadAndVerify(Request $request)
    {
        set_time_limit(300); // Increase to 5 minutes for batch processing
        $request->validate([
            'lga' => 'required|exists:lgas,id',
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240', // 10MB max
        ]);

        try {
            $lga = LGA::findOrFail($request->lga);
            $file = $request->file('file');
            
            // Read the file and extract NIN column
            $ninData = $this->extractNINsFromFile($file);
            
            if (empty($ninData['nins'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No NIN column found in the uploaded file. Please ensure your file has a column named "NIN".'
                ]);
            }

            // Process NIN verification in batches
            $results = $this->batchVerifyNINs($ninData['nins'], $lga);

            return response()->json([
                'success' => true,
                'message' => "Processed {$ninData['total_rows']} rows. Found {$ninData['nin_count']} NINs.",
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Upload and verification error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing file: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Extract NINs from uploaded file
     */
    private function extractNINsFromFile($file)
    {
        HeadingRowFormatter::default('none'); // Don't format headings
        
        $data = Excel::toArray([], $file);
        $rawNins = [];
        $cleanNins = [];
        $totalRows = 0;

        if (!empty($data[0])) {
            $headers = $data[0][0] ?? [];
            $ninColumnIndex = null;

            // Find NIN column (case insensitive)
            foreach ($headers as $index => $header) {
                $cleanHeader = trim(strtolower($header));
                
                if (stripos($cleanHeader, 'nin') !== false) {
                    $ninColumnIndex = $index;
                    break;
                }
            }

            if ($ninColumnIndex !== null) {
                // Skip header row and extract NINs
                for ($i = 1; $i < count($data[0]); $i++) {
                    $row = $data[0][$i];
                    $totalRows++;
                    
                    if (isset($row[$ninColumnIndex])) {
                        $rawNin = trim($row[$ninColumnIndex]);
                        
                        if (!empty($rawNin)) {
                            $rawNins[] = $rawNin;
                            $cleanNin = $this->ninService->cleanNIN($rawNin);
                            
                            if ($cleanNin) {
                                $cleanNins[] = $cleanNin;
                            }
                        }
                    }
                }
            }

            return [
                'nins' => array_unique($cleanNins), // Remove duplicates
                'raw_nins' => array_unique($rawNins),
                'total_rows' => $totalRows,
                'nin_count' => count($cleanNins)
            ];
        }
        
        return [
            'nins' => [],
            'raw_nins' => [],
            'total_rows' => 0,
            'nin_count' => 0
        ];
    }

    /**
     * Get random ward for LGA
     */
    private function getRandomWard($lgaId)
    {
        $ward = Ward::where('lga_id', $lgaId)->inRandomOrder()->first();
        return $ward ? $ward->name : 'Not specified';
    }

    /**
     * Get random polling unit for LGA
     */
    private function getRandomPollingUnit($lgaId)
    {
        $pollingUnit = PollingUnit::inRandomOrder()
            ->whereHas('ward', function($query) use ($lgaId) {
                $query->where('lga_id', $lgaId);
            })->first();
        return $pollingUnit ? $pollingUnit->name : 'Not specified';
    }

    /**
     * Pre-filter NINs based on environment
     * - Test: Use hardcoded test NINs
     * - Live: Check external database for existing NINs
     */
    private function preFilterNINs(array $nins, string $environment): array
    {
        if ($environment === 'test') {
            // In test mode, return all NINs (hardcoded filtering done in batchVerifyNINs)
            return $nins;
        }

        // Live mode: Check external database
        try {
            $externalConnection = $this->getExternalDatabaseConnection();
            
            if (!$externalConnection) {
                Log::error('Could not connect to external database');
                return $nins; // Return all NINs if connection fails
            }

            $existingNins = [];
            foreach ($nins as $nin) {
                try {
                    $result = $externalConnection->select("SELECT nin FROM members WHERE nin = ? LIMIT 1", [$nin]);
                    if (!empty($result)) {
                        $existingNins[] = $nin;
                    }
                } catch (\Exception $e) {
                    Log::error("Error checking NIN $nin in external database: " . $e->getMessage());
                }
            }

            $externalConnection->disconnect();
            
            // Log filtered NINs
            Log::info('External database check:', [
                'total_nins' => count($nins),
                'existing_in_external' => count($existingNins),
                'filtered_nins' => array_diff($nins, $existingNins)
            ]);

            // Return NINs that don't exist in external database
            return array_diff($nins, $existingNins);

        } catch (\Exception $e) {
            Log::error('Error in preFilterNINs: ' . $e->getMessage());
            return $nins; // Return all NINs if there's an error
        }
    }

    /**
     * Get external database connection
     */
    private function getExternalDatabaseConnection()
    {
        try {
            $config = [
                'host' => env('DB_EXTERNAL_HOST', '127.0.0.1'),
                'port' => env('DB_EXTERNAL_PORT', '3306'),
                'database' => env('DB_EXTERNAL_DATABASE', 'enumerator'),
                'username' => env('DB_EXTERNAL_USERNAME', 'root'),
                'password' => env('DB_EXTERNAL_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
            ];

            $capsule = new \Illuminate\Database\Capsule\Manager;
            $capsule->addConnection($config, 'external');
            $capsule->setAsGlobal();
            $capsule->bootEloquent();

            return $capsule->getConnection('external');
        } catch (\Exception $e) {
            Log::error('External database connection error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Batch verify NINs and create member records
     */
    private function batchVerifyNINs(array $nins, LGA $lga)
    {
        $results = [
            'total' => count($nins),
            'verified' => 0,
            'failed' => 0,
            'already_exists' => 0,
            'members_created' => 0,
            'errors' => []
        ];

        $environment = config('services.prembly.env', 'test');
        
        // Pre-filter NINs based on environment
        $filteredNins = $this->preFilterNINs($nins, $environment);
        
        if ($environment === 'test') {
            // Hardcoded test NINs that already exist (for testing)
            $hardcodedTestNins = ['84726139034', '19374362859', '56281933746'];
            $filteredNins = array_diff($filteredNins, $hardcodedTestNins);
            $results['already_exists'] += count(array_intersect($nins, $hardcodedTestNins));
        }

        foreach ($filteredNins as $nin) {
            try {
                // Verify NIN
                $verificationData = $this->ninService->verifyNIN($nin);
                $memberData = $this->ninService->extractMemberData($verificationData, $nin);
                
                // Check if member already exists in local database (only after verification)
                if ($memberData['nin'] && Member::where('nin', $memberData['nin'])->exists()) {
                    $results['already_exists']++;
                    continue;
                }

                if ($memberData['nin']) {
                    // Create member record
                    $member = Member::create([
                        'nin' => $memberData['nin'],
                        'first_name' => $memberData['first_name'],
                        'last_name' => $memberData['last_name'],
                        'gender' => $memberData['gender'],
                        'date_of_birth' => $memberData['date_of_birth'],
                        'phone_number' => $memberData['phone_number'],
                        'email' => $memberData['email'],
                        'state' => 'Osun', // Hardcoded as requested
                        'lga' => $lga->name,
                        'ward' => $this->getRandomWard($lga->id),
                        'polling_unit' => $this->getRandomPollingUnit($lga->id),
                        'residential_address' => $memberData['residential_address'] ?: 'Not specified',
                        'membership_number' => $this->generateMembershipNumber($lga->name),
                        'registration_date' => now()->toDateString(),
                        'agentcode' => '2', // Hardcoded as requested
                    ]);

                    $results['verified']++;
                    $results['members_created']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "NIN {$nin}: Invalid verification response";
                }

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "NIN {$nin}: " . $e->getMessage();
                Log::error("NIN verification failed for {$nin}", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Generate LGA abbreviation
     */
    private function generateLGAAbbreviation($lgaName)
    {
        // Remove common words and get first 3 letters of remaining words
        $words = explode(' ', $lgaName);
        $abbreviation = '';
        
        foreach ($words as $word) {
            if (strlen($word) >= 3) {
                $abbreviation .= strtoupper(substr($word, 0, 3));
                if (strlen($abbreviation) >= 3) {
                    break;
                }
            }
        }
        
        // Fallback: just take first 3 letters
        if (strlen($abbreviation) < 3) {
            $abbreviation = strtoupper(substr($lgaName, 0, 3));
        }
        
        return $abbreviation;
    }

    /**
     * Generate unique membership number in format A/OS/[LGA_ABBR]/[7-digit number]
     */
    private function generateMembershipNumber($lgaName)
    {
        $lgaAbbreviation = $this->generateLGAAbbreviation($lgaName);
        $prefix = "A/OS/{$lgaAbbreviation}/";
        
        // Get the highest number for this LGA prefix
        $lastMember = Member::where('membership_number', 'like', $prefix . '%')
            ->orderBy('membership_number', 'desc')
            ->first();
        
        if ($lastMember) {
            // Extract the number part
            $lastNumber = substr($lastMember->membership_number, strlen($prefix));
            $nextNumber = (int)$lastNumber + 1;
        } else {
            // Start from 1000000 for new LGA
            $nextNumber = 1000000;
        }
        
        return $prefix . str_pad($nextNumber, 7, '0', STR_PAD_LEFT);
    }

    /**
     * Display members list
     */
    public function index()
    {
        $members = Member::orderBy('created_at', 'desc')->paginate(50);
        return view('members.index', compact('members'));
    }
}
