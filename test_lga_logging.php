<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Http\Request;
use App\Http\Controllers\EnumeratorController;

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing LGA Fetch Logging\n";
echo "========================\n\n";

$controller = new EnumeratorController(app(\App\Services\EnumeratorDataService::class));

// Test 1: Get LGAs
echo "Test 1: Fetching LGAs...\n";
echo "------------------------\n";

$request = new Request();
$response = $controller->getLGAs();

echo "Response Status: " . $response->getStatusCode() . "\n";
$data = $response->getData();
echo "Success: " . ($data->success ? 'YES' : 'NO') . "\n";
echo "LGAs Count: " . count($data->data) . "\n\n";

// Test 2: Get Wards (using first LGA)
if (!empty($data->data)) {
    $firstLGA = $data->data[0];
    echo "Test 2: Fetching wards for LGA: " . $firstLGA->name . "...\n";
    echo "------------------------------------------------\n";
    
    $wardsRequest = new Request(['lga' => $firstLGA->name]);
    $wardsResponse = $controller->getWardsByLGA($wardsRequest);
    
    echo "Response Status: " . $wardsResponse->getStatusCode() . "\n";
    $wardsData = $wardsResponse->getData();
    echo "Success: " . ($wardsData->success ? 'YES' : 'NO') . "\n";
    echo "Wards Count: " . count($wardsData->data) . "\n\n";
    
    // Test 3: Get Polling Units (using first ward if available)
    if (!empty($wardsData->data)) {
        $firstWard = $wardsData->data[0];
        echo "Test 3: Fetching polling units for ward: " . $firstWard->name . "...\n";
        echo "---------------------------------------------------------\n";
        
        $pollingRequest = new Request(['ward' => $firstWard->name]);
        $pollingResponse = $controller->getPollingUnitsByWard($pollingRequest);
        
        echo "Response Status: " . $pollingResponse->getStatusCode() . "\n";
        $pollingData = $pollingResponse->getData();
        echo "Success: " . ($pollingData->success ? 'YES' : 'NO') . "\n";
        echo "Polling Units Count: " . count($pollingData->data) . "\n\n";
    }
}

echo "========================\n";
echo "Logging test completed!\n";
echo "Check storage/logs/laravel.log for detailed logs.\n";
echo "========================\n";
