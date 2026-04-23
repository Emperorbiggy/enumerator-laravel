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
            'state' => 'required|string',
            'lga' => 'nullable|exists:lgas,id',
            'ward' => 'nullable|exists:wards,id',
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240', // 10MB max
            'skip_verification' => 'nullable|boolean',
        ]);

        try {
            $state = $request->state;
            $lga = null;
            $ward = null;
            
            // If LGA is provided, use it; otherwise we'll randomize
            if ($request->lga) {
                $lga = LGA::findOrFail($request->lga);
            }
            
            // If Ward is provided, use it; otherwise we'll randomize
            if ($request->ward) {
                $ward = Ward::findOrFail($request->ward);
            }
            
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
            $skipVerification = $request->boolean('skip_verification', false);
            $results = $this->batchVerifyNINs($ninData['nins'], $lga, $ward, $state, $skipVerification, $ninData['file_data']);

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
        $fileData = [];

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
                                // Store the entire row data for this NIN
                                // Make sure we have enough elements in headers and row
                                if (count($headers) === count($row)) {
                                    $fileData[$cleanNin] = array_combine($headers, $row);
                                } else {
                                    // Fallback: create associative array manually
                                    $rowData = [];
                                    for ($j = 0; $j < count($headers) && $j < count($row); $j++) {
                                        $rowData[$headers[$j]] = $row[$j] ?? '';
                                    }
                                    $fileData[$cleanNin] = $rowData;
                                }
                                                            }
                        }
                    }
                }
            }

            return [
                'nins' => array_unique($cleanNins), // Remove duplicates
                'raw_nins' => array_unique($rawNins),
                'total_rows' => $totalRows,
                'nin_count' => count($cleanNins),
                'file_data' => $fileData
            ];
        }
        
        return [
            'nins' => [],
            'raw_nins' => [],
            'total_rows' => 0,
            'nin_count' => 0,
            'file_data' => []
        ];
    }

    /**
     * Extract member data from uploaded file (for skip verification mode)
     */
    private function extractMemberDataFromFile($nin, $fileRow)
    {
        // Default values
        $memberData = [
            'nin' => $nin,
            'first_name' => 'N/A',
            'last_name' => 'N/A',
            'gender' => 'N/A',
            'date_of_birth' => '1990-01-01', // Default date of birth
            'phone_number' => 'N/A',
            'email' => 'N/A',
            'residential_address' => 'N/A',
        ];

        if (empty($fileRow)) {
            return $memberData;
        }

        // Map columns (case insensitive)
        $memberData['first_name'] = $this->getValueFromRow($fileRow, ['first name', 'firstname', 'first_name']) ?? 'N/A';
        $memberData['last_name'] = $this->getValueFromRow($fileRow, ['last name', 'lastname', 'last_name']) ?? 'N/A';
        $memberData['gender'] = $this->getValueFromRow($fileRow, ['gender']) ?? 'N/A';
        $memberData['phone_number'] = $this->getValueFromRow($fileRow, ['phone number', 'phone', 'phone_number']) ?? 'N/A';
        $memberData['residential_address'] = $this->getValueFromRow($fileRow, ['address', 'residential address', 'residential_address']) ?? 'N/A';
        
        // Try to get date of birth from file (optional column)
        $dateOfBirth = $this->getValueFromRow($fileRow, ['date of birth', 'dob', 'date_of_birth', 'birthdate']);
        if ($dateOfBirth) {
            $memberData['date_of_birth'] = $this->formatDate($dateOfBirth);
        }
        
        // Generate email if not provided
        $email = $this->getValueFromRow($fileRow, ['email']);
        if ($email) {
            $memberData['email'] = $email;
        } else {
            $memberData['email'] = strtolower(str_replace(' ', '', $memberData['first_name'] . $memberData['last_name'])) . '@example.com';
        }

        return $memberData;
    }

    /**
     * Get value from row using multiple possible column names (case insensitive)
     */
    private function getValueFromRow($row, $possibleColumns)
    {
        foreach ($possibleColumns as $column) {
            foreach ($row as $key => $value) {
                if (strtolower(trim($key)) === strtolower(trim($column))) {
                    return trim($value) ?: null;
                }
            }
        }
        return null;
    }

    /**
     * Format date from various formats to Y-m-d
     */
    private function formatDate($date)
    {
        if (empty($date)) {
            return '1990-01-01';
        }

        try {
            // Try to create DateTime object from various formats
            $dateTime = new \DateTime($date);
            return $dateTime->format('Y-m-d');
        } catch (\Exception $e) {
            // If date parsing fails, return default date
            return '1990-01-01';
        }
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
     * Get random polling unit for LGA or Ward
     */
    private function getRandomPollingUnit($locationId, $isWard = false)
    {
        if ($isWard) {
            // Get polling unit for specific ward
            $pollingUnit = PollingUnit::where('ward_id', $locationId)->inRandomOrder()->first();
        } else {
            // Get polling unit for LGA
            $pollingUnit = PollingUnit::inRandomOrder()
                ->whereHas('ward', function($query) use ($locationId) {
                    $query->where('lga_id', $locationId);
                })->first();
        }
        return $pollingUnit ? $pollingUnit->name : 'Not specified';
    }

    /**
     * Pre-filter NINs based on environment
     * - Test: Use hardcoded test NINs + check local database
     * - Live: Check both external and local databases for existing NINs
     */
    private function preFilterNINs(array $nins, string $environment): array
    {
        $existingNins = [];
        $externalExistingNins = [];
        $localExistingNins = [];
        
        if ($environment === 'test') {
            // Test mode: Check local database + hardcoded test NINs
            Log::info('TEST MODE: Checking local database for existing NINs');
            
            foreach ($nins as $nin) {
                if (Member::where('nin', $nin)->exists()) {
                    $localExistingNins[] = $nin;
                    Log::info("Found existing NIN in local database: $nin");
                }
            }
            
            // Add hardcoded test NINs
            $hardcodedTestNins = ['84726139034', '19374362859', '56281933746'];
            $hardcodedFound = array_intersect($nins, $hardcodedTestNins);
            
            $existingNins = array_merge($localExistingNins, $hardcodedFound);
            $existingNins = array_unique($existingNins);
            
            Log::info('TEST MODE PRE-FILTER RESULTS:', [
                'total_nins' => count($nins),
                'existing_in_local_db' => count($localExistingNins),
                'hardcoded_test_nins' => count($hardcodedFound),
                'total_existing' => count($existingNins),
                'new_nins_to_process' => count($nins) - count($existingNins),
                'cost_saved' => count($existingNins) . ' API calls avoided'
            ]);
            
        } else {
            // Live mode: Check both external and local databases
            Log::info('LIVE MODE: Checking both external and local databases for existing NINs');
            
            // Check external database
            try {
                Log::info('Step 1: Checking external database');
                $externalConnection = $this->getExternalDatabaseConnection();
                
                if ($externalConnection) {
                    foreach ($nins as $nin) {
                        try {
                            $result = $externalConnection->select("SELECT nin FROM members WHERE nin = ? LIMIT 1", [$nin]);
                            if (!empty($result)) {
                                $externalExistingNins[] = $nin;
                                Log::info("Found existing NIN in external database: $nin");
                            }
                        } catch (\Exception $e) {
                            Log::error("Error checking NIN $nin in external database: " . $e->getMessage());
                        }
                    }
                    $externalConnection->disconnect();
                    Log::info('External database check completed');
                } else {
                    Log::warning('Could not connect to external database - skipping external check');
                }
            } catch (\Exception $e) {
                Log::error('Error in external database check: ' . $e->getMessage());
            }
            
            // Check local database
            Log::info('Step 2: Checking local database');
            foreach ($nins as $nin) {
                if (Member::where('nin', $nin)->exists()) {
                    $localExistingNins[] = $nin;
                    Log::info("Found existing NIN in local database: $nin");
                }
            }
            
            // Merge existing NINs from both databases
            $existingNins = array_unique(array_merge($externalExistingNins, $localExistingNins));
            
            Log::info('LIVE MODE PRE-FILTER RESULTS:', [
                'total_nins' => count($nins),
                'existing_in_external_db' => count($externalExistingNins),
                'existing_in_local_db' => count($localExistingNins),
                'total_unique_existing' => count($existingNins),
                'new_nins_to_process' => count($nins) - count($existingNins),
                'cost_saved' => count($existingNins) . ' API calls avoided',
                'external_existing_list' => $externalExistingNins,
                'local_existing_list' => $localExistingNins,
                'duplicates_found' => count(array_intersect($externalExistingNins, $localExistingNins))
            ]);
        }
        
        // Return NINs that don't exist in either database
        return array_diff($nins, $existingNins);
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

            Log::info('EXTERNAL DATABASE CONFIG:', [
                'host' => $config['host'],
                'port' => $config['port'],
                'database' => $config['database'],
                'username' => $config['username'],
                'password_set' => !empty($config['password']) ? 'YES' : 'NO'
            ]);

            $capsule = new \Illuminate\Database\Capsule\Manager;
            $capsule->addConnection($config, 'external');
            $capsule->setAsGlobal();
            $capsule->bootEloquent();

            $connection = $capsule->getConnection('external');
            
            // Test the connection
            $connection->select('SELECT 1');
            
            Log::info('EXTERNAL DATABASE CONNECTION: SUCCESS - Connection established and tested');
            
            return $connection;
        } catch (\Exception $e) {
            Log::error('EXTERNAL DATABASE CONNECTION: FAILED - ' . $e->getMessage());
            Log::error('Connection error details:', [
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine()
            ]);
            return null;
        }
    }

    /**
     * Batch verify NINs and create member records
     */
    private function batchVerifyNINs(array $nins, ?LGA $lga, ?Ward $ward, string $state, bool $skipVerification = false, array $fileData = [])
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
        
        // Calculate how many NINs were filtered out (already exist)
        $filteredOutCount = count($nins) - count($filteredNins);
        $results['already_exists'] += $filteredOutCount;

        foreach ($filteredNins as $nin) {
            try {
                if ($skipVerification) {
                    // Skip verification - get data from uploaded file
                    $memberData = $this->extractMemberDataFromFile($nin, $fileData[$nin] ?? []);
                    $memberData['photo'] = 'https://humanity.peoplefirst.org.ng/images/avatar.png';
                    Log::info("SKIPPED VERIFICATION: Creating member for NIN: $nin from file data");
                } else {
                    // Verify NIN (only NINs that don't exist in either database reach here)
                    $verificationData = $this->ninService->verifyNIN($nin);
                    $memberData = $this->ninService->extractMemberData($verificationData, $nin);
                    
                    // Download and store photo if available
                    if (!empty($memberData['photo'])) {
                        $localPhotoUrl = $this->ninService->downloadAndStorePhoto($memberData['photo'], $nin);
                        if ($localPhotoUrl) {
                            $memberData['photo'] = $localPhotoUrl;
                        } else {
                            $memberData['photo'] = null;
                        }
                    } else {
                        $memberData['photo'] = null;
                    }
                }

                if ($memberData['nin'] && !empty($memberData['first_name'])) {
                    // Only create member if verification was successful and we have valid data
                    Log::info("Creating member for NIN: $nin - Verification successful");
                    if ($ward) {
                        // Ward provided: randomize polling unit only
                        $pollingUnit = $this->getRandomPollingUnit($ward->id, true); // true = ward-level
                        $wardName = $ward->name;
                        $lgaName = $lga->name;
                        Log::info("Using provided LGA: {$lgaName}, Ward: {$wardName}, randomizing polling unit: {$pollingUnit}");
                    } elseif ($lga) {
                        // LGA provided: randomize ward and polling unit
                        $wardName = $this->getRandomWard($lga->id);
                        $pollingUnit = $this->getRandomPollingUnit($lga->id, false); // false = lga-level
                        $lgaName = $lga->name;
                        Log::info("Using provided LGA: {$lgaName}, randomizing ward: {$wardName}, polling unit: {$pollingUnit}");
                    } else {
                        // State only: randomize LGA, ward, and polling unit
                        $randomLga = LGA::inRandomOrder()->first();
                        $wardName = $this->getRandomWard($randomLga->id);
                        $pollingUnit = $this->getRandomPollingUnit($randomLga->id, false); // false = lga-level
                        $lgaName = $randomLga->name;
                        Log::info("State only: Randomized LGA: {$lgaName}, ward: {$wardName}, polling unit: {$pollingUnit}");
                    }
                    
                    // Create member record
                    $member = Member::create([
                        'nin' => $memberData['nin'],
                        'first_name' => $memberData['first_name'],
                        'last_name' => $memberData['last_name'],
                        'gender' => $memberData['gender'],
                        'date_of_birth' => $memberData['date_of_birth'],
                        'phone_number' => $memberData['phone_number'],
                        'email' => $memberData['email'],
                        'state' => $state,
                        'lga' => $lgaName,
                        'ward' => $wardName,
                        'polling_unit' => $pollingUnit,
                        'residential_address' => $memberData['residential_address'],
                        'photo_path' => $memberData['photo'],
                        'membership_number' => $this->generateMembershipNumber($lgaName),
                        'registration_date' => now(),
                        'agentcode' => '2', // Hardcoded as requested
                    ]);

                    $results['verified']++;
                    $results['members_created']++;
                } else {
                    if (empty($memberData['first_name'])) {
                        $results['failed']++;
                        $results['errors'][] = "NIN {$nin}: Verification failed - Record not found or invalid NIN";
                        Log::warning("NIN verification failed - no valid data returned", ['nin' => $nin]);
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "NIN {$nin}: Invalid verification response";
                    }
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
