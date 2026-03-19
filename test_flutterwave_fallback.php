<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Http\Request;
use App\Http\Controllers\PaystackController;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Flutterwave Fallback Implementation\n";
echo "==========================================\n\n";

// Test 1: List Banks with Flutterwave
echo "Test 1: Testing listBanks with Flutterwave fallback\n";
echo "----------------------------------------------------\n";

$controller = new PaystackController();
$request = new Request();

echo "Making request to /api/paystack/banks...\n";

// Temporarily modify Paystack config to simulate failure
config(['services.paystack.secret_key' => 'invalid_key_to_trigger_fallback']);

$response = $controller->listBanks($request);

echo "Response Status: " . $response->getStatusCode() . "\n";
echo "Response Data: " . json_encode($response->getData(), JSON_PRETTY_PRINT) . "\n\n";

// Test 2: Resolve Account with Flutterwave
echo "Test 2: Testing resolveAccount with Flutterwave fallback\n";
echo "--------------------------------------------------------\n";

$resolveRequest = new Request([
    'account_number' => '0690000032',
    'bank_code' => '044'
]);

echo "Making request to /api/paystack/resolve-account...\n";
echo "Account Number: 0690000032\n";
echo "Bank Code: 044 (Access Bank)\n\n";

$response = $controller->resolveAccount($resolveRequest);

echo "Response Status: " . $response->getStatusCode() . "\n";
echo "Response Data: " . json_encode($response->getData(), JSON_PRETTY_PRINT) . "\n\n";

echo "==========================================\n";
echo "Test completed. Check logs for detailed info.\n";
echo "==========================================\n";
