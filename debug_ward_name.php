<?php

require __DIR__.'/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\EnumeratorDataService;

echo "Ward Name Debug Test\n";
echo "====================\n\n";

$service = new EnumeratorDataService();

// Test the problematic ward name
$testWardName = "OKINNI/OLORUNSOGO/OFATEDO";

echo "Testing ward name: '{$testWardName}'\n";
echo "URL encoded: " . urlencode($testWardName) . "\n";
echo "URL decoded: " . urldecode($testWardName) . "\n\n";

echo "Step 1: Getting all LGAs...\n";
$lgas = $service->getLGAs();
echo "Found " . count($lgas) . " LGAs\n\n";

echo "Step 2: Searching for ward in all LGAs...\n";
$found = false;
$totalWards = 0;

foreach ($lgas as $lga) {
    echo "Checking LGA: {$lga['name']} (ID: {$lga['id']})\n";
    
    $wards = $service->getWardsByLGA($lga['name']);
    $totalWards += count($wards);
    
    echo "  Found " . count($wards) . " wards\n";
    
    foreach ($wards as $ward) {
        $wardNameFromApi = trim($ward['name']);
        $searchName = trim($testWardName);
        
        // Show similar looking wards
        if (
            strpos(strtolower($wardNameFromApi), strtolower('okinni')) !== false ||
            strpos(strtolower($wardNameFromApi), strtolower('olorunsogo')) !== false ||
            strpos(strtolower($wardNameFromApi), strtolower('ofatedo')) !== false
        ) {
            echo "  SIMILAR WARD FOUND: '{$wardNameFromApi}' (ID: {$ward['id']})\n";
        }
        
        // Exact match
        if (strcasecmp($wardNameFromApi, $searchName) === 0) {
            echo "  ✅ EXACT MATCH FOUND: '{$wardNameFromApi}' (ID: {$ward['id']})\n";
            $found = true;
            break 2;
        }
        
        // Normalized match
        $normalizedSearch = preg_replace('/\s+/', ' ', $searchName);
        $normalizedFound = preg_replace('/\s+/', ' ', $wardNameFromApi);
        
        if (strcasecmp($normalizedFound, $normalizedSearch) === 0) {
            echo "  ✅ NORMALIZED MATCH FOUND: '{$wardNameFromApi}' (ID: {$ward['id']})\n";
            $found = true;
            break 2;
        }
        
        // URL decoded match
        $urlDecodedSearch = urldecode($searchName);
        if (strcasecmp($wardNameFromApi, $urlDecodedSearch) === 0) {
            echo "  ✅ URL DECODED MATCH FOUND: '{$wardNameFromApi}' (ID: {$ward['id']})\n";
            $found = true;
            break 2;
        }
    }
    
    echo "\n";
}

echo "Search Results:\n";
echo "Total LGAs checked: " . count($lgas) . "\n";
echo "Total wards checked: {$totalWards}\n";
echo "Ward found: " . ($found ? 'YES' : 'NO') . "\n\n";

if (!$found) {
    echo "Debugging suggestions:\n";
    echo "1. Check if the ward name '{$testWardName}' exists in the external API\n";
    echo "2. The slashes might be encoded differently in the API\n";
    echo "3. Try variations like 'OKINNI OLORUNSOGO OFATEDO' (without slashes)\n";
    echo "4. Check the API response for the exact format of ward names\n";
}

echo "\n====================\n";
