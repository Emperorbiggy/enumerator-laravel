<?php

require __DIR__.'/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\AdminController;
use Illuminate\Http\Request;

echo "Enumerator Performance Test\n";
echo "============================\n\n";

$controller = new AdminController();

// Test the enumerator performance endpoint
echo "Testing Enumerator Performance API...\n";
echo "------------------------------------\n";

$request = new Request();
$response = $controller->enumeratorPerformance($request);

echo "Response Status: " . $response->getStatusCode() . "\n";
$data = $response->getData();

echo "Success: " . ($data->success ? 'YES' : 'NO') . "\n";

if ($data->success) {
    echo "\n=== PERFORMANCE STATISTICS ===\n";
    $stats = $data->data->stats;
    echo "Total Enumerators: " . $stats->total_enumerators . "\n";
    echo "Total Members Registered: " . $stats->total_members_registered . "\n";
    echo "Enumerators with Members: " . $stats->enumerators_with_members . "\n";
    echo "Enumerators without Members: " . $stats->enumerators_without_members . "\n";
    echo "Average Members per Enumerator: " . round($stats->average_members_per_enumerator, 2) . "\n";
    
    if ($stats->top_performer) {
        echo "Top Performer: " . $stats->top_performer->full_name . " (" . $stats->top_performer->members_registered . " members)\n";
    }
    
    echo "\n=== TOP 10 PERFORMERS ===\n";
    foreach ($data->data->top_performers as $index => $performer) {
        echo ($index + 1) . ". " . $performer->full_name . " - " . $performer->members_registered . " members (Code: " . $performer->code . ")\n";
    }
    
    echo "\n=== PERFORMANCE BY LGA ===\n";
    foreach ($data->data->performance_by_lga as $index => $lga) {
        echo ($index + 1) . ". " . $lga->lga . " - " . $lga->total_members . " members by " . $lga->enumerator_count . " enumerators\n";
    }
    
    echo "\nResponse Time: " . $data->response_time_ms . "ms\n";
} else {
    echo "Error: " . $data->message . "\n";
    if (isset($data->error)) {
        echo "Details: " . $data->error . "\n";
    }
}

echo "\n============================\n";
echo "API Endpoint: /admin/enumerator-performance\n";
echo "============================\n";
