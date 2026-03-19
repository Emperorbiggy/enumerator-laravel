<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Http\Request;
use App\Http\Controllers\PaystackController;

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Direct Flutterwave API Test\n";
echo "===========================\n\n";

// Use reflection to access private methods
$controller = new PaystackController();
$reflection = new ReflectionClass($controller);

// Test listBanksFromFlutterwave directly
echo "Testing Flutterwave Banks API Directly:\n";
echo "---------------------------------------\n";

$listBanksMethod = $reflection->getMethod('listBanksFromFlutterwave');
$listBanksMethod->setAccessible(true);

$request = new Request();
$response = $listBanksMethod->invoke($controller, $request);

if ($response) {
    $data = $response->getData();
    echo "Status: " . ($data->status ? 'SUCCESS' : 'FAILED') . "\n";
    echo "Provider: " . $data->provider . "\n";
    echo "Banks Count: " . count($data->data) . "\n";
    echo "Sample Banks:\n";
    for ($i = 0; $i < min(5, count($data->data)); $i++) {
        echo "  - " . $data->data[$i]->name . " (" . $data->data[$i]->code . ")\n";
    }
} else {
    echo "Flutterwave banks API failed\n";
}

echo "\n";

// Test resolveAccountFromFlutterwave directly
echo "Testing Flutterwave Resolve API Directly:\n";
echo "------------------------------------------\n";

$resolveMethod = $reflection->getMethod('resolveAccountFromFlutterwave');
$resolveMethod->setAccessible(true);

$resolveRequest = new Request([
    'account_number' => '0690000032',
    'bank_code' => '044'
]);

echo "Resolving: 0690000032 (Access Bank - 044)\n";
$response = $resolveMethod->invoke($controller, $resolveRequest);

if ($response) {
    $data = $response->getData();
    echo "Status: " . ($data->status ? 'SUCCESS' : 'FAILED') . "\n";
    echo "Provider: " . $data->provider . "\n";
    if ($data->status) {
        echo "Account Name: " . $data->data->account_name . "\n";
        echo "Account Number: " . $data->data->account_number . "\n";
    }
} else {
    echo "Flutterwave resolve API failed\n";
}

echo "\n===========================\n";
