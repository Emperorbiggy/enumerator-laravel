<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Http\Request;
use App\Http\Controllers\ExternalMembersController;

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "External Members API Test\n";
echo "==========================\n\n";

$controller = new ExternalMembersController();

// Test 1: Test Database Connection
echo "Test 1: Testing External Database Connection...\n";
echo "------------------------------------------------\n";

$request = new Request();
$response = $controller->testConnection();

echo "Response Status: " . $response->getStatusCode() . "\n";
$data = $response->getData();
echo "Success: " . ($data->success ? 'YES' : 'NO') . "\n";
echo "Message: " . $data->message . "\n";

if ($data->success) {
    echo "Database: " . $data->data->database . "\n";
    echo "Total Members: " . $data->data->total_members . "\n";
    echo "Connection Status: " . $data->data->connection_status . "\n";
}

echo "\n";

// Test 2: Get Members List (if connection successful)
if ($data->success) {
    echo "Test 2: Fetching Members List...\n";
    echo "---------------------------------\n";
    
    $membersRequest = new Request(['per_page' => 5]);
    $membersResponse = $controller->index($membersRequest);
    
    echo "Response Status: " . $membersResponse->getStatusCode() . "\n";
    $membersData = $membersResponse->getData();
    echo "Success: " . ($membersData->success ? 'YES' : 'NO') . "\n";
    
    if ($membersData->success) {
        echo "Members Count: " . count($membersData->data) . "\n";
        echo "Total Count: " . $membersData->pagination->total . "\n";
        echo "Current Page: " . $membersData->pagination->current_page . "\n";
        echo "Response Time: " . $membersData->response_time_ms . "ms\n";
        
        if (!empty($membersData->data)) {
            echo "\nSample Members:\n";
            foreach ($membersData->data as $member) {
                echo "- ID: {$member->id}, Name: {$member->first_name} {$member->last_name}, Member #: {$member->membership_number}\n";
            }
        }
    }
    
    echo "\n";
    
    // Test 3: Get Statistics
    echo "Test 3: Fetching Statistics...\n";
    echo "----------------------------\n";
    
    $statsResponse = $controller->statistics($request);
    
    echo "Response Status: " . $statsResponse->getStatusCode() . "\n";
    $statsData = $statsResponse->getData();
    echo "Success: " . ($statsData->success ? 'YES' : 'NO') . "\n";
    
    if ($statsData->success) {
        echo "Total Members: " . $statsData->data->total_members . "\n";
        echo "States Count: " . count($statsData->data->members_by_state) . "\n";
        echo "LGAs Count: " . count($statsData->data->members_by_lga) . "\n";
        echo "Recent Registrations: " . count($statsData->data->recent_registrations) . "\n";
        echo "Response Time: " . $statsData->response_time_ms . "ms\n";
    }
}

echo "\n==========================\n";
echo "API Test completed!\n";
echo "Available endpoints:\n";
echo "- GET /api/external-members/test-connection\n";
echo "- GET /api/external-members/?per_page=50&page=1\n";
echo "- GET /api/external-members/statistics\n";
echo "- GET /api/external-members/{id}\n";
echo "==========================\n";
