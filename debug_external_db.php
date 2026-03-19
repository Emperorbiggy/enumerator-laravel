<?php

require __DIR__.'/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "External Database Debug Test\n";
echo "============================\n\n";

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Env;

// Check if .env is being loaded
echo "1. Environment Variables Check:\n";
echo "-------------------------------\n";
echo "DB_EXTERNAL_HOST: " . (Env::get('DB_EXTERNAL_HOST') ?? 'NOT FOUND') . "\n";
echo "DB_EXTERNAL_PORT: " . (Env::get('DB_EXTERNAL_PORT') ?? 'NOT FOUND') . "\n";
echo "DB_EXTERNAL_DATABASE: " . (Env::get('DB_EXTERNAL_DATABASE') ?? 'NOT FOUND') . "\n";
echo "DB_EXTERNAL_USERNAME: " . (Env::get('DB_EXTERNAL_USERNAME') ?? 'NOT FOUND') . "\n";
echo "DB_EXTERNAL_PASSWORD: " . (Env::get('DB_EXTERNAL_PASSWORD') ? 'SET' : 'NOT FOUND') . "\n";

echo "\n2. Configuration Check:\n";
echo "------------------------\n";
$dbConfig = Config::get('database.connections.external_mysql');
if ($dbConfig) {
    echo "Configuration loaded: YES\n";
    echo "Driver: " . ($dbConfig['driver'] ?? 'NOT SET') . "\n";
    echo "Host: " . ($dbConfig['host'] ?? 'NOT SET') . "\n";
    echo "Port: " . ($dbConfig['port'] ?? 'NOT SET') . "\n";
    echo "Database: " . ($dbConfig['database'] ?? 'NOT SET') . "\n";
    echo "Username: " . ($dbConfig['username'] ?? 'NOT SET') . "\n";
    echo "Password: " . (empty($dbConfig['password']) ? 'NOT SET' : 'SET') . "\n";
} else {
    echo "Configuration loaded: NO\n";
    echo "External MySQL connection not found in config!\n";
}

echo "\n3. Clear Configuration Cache:\n";
echo "--------------------------------\n";
echo "Run these commands on your server:\n";
echo "php artisan config:clear\n";
echo "php artisan cache:clear\n";
echo "php artisan config:cache\n";

echo "\n4. Test Connection:\n";
echo "-------------------\n";

try {
    use Illuminate\Support\Facades\DB;
    
    // Test connection
    $pdo = DB::connection('external_mysql')->getPdo();
    echo "Database Connection: SUCCESS\n";
    
    // Test query
    $result = DB::connection('external_mysql')->select('SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ?', [$dbConfig['database']]);
    echo "Tables in database: " . $result[0]->count . "\n";
    
    // Check if members table exists
    $membersTable = DB::connection('external_mysql')->select('SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ? AND table_name = ?', [$dbConfig['database'], 'members']);
    echo "Members table exists: " . ($membersTable[0]->count > 0 ? 'YES' : 'NO') . "\n";
    
    if ($membersTable[0]->count > 0) {
        $membersCount = DB::connection('external_mysql')->table('members')->count();
        echo "Total members: " . $membersCount . "\n";
    }
    
} catch (Exception $e) {
    echo "Database Connection: FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
}

echo "\n============================\n";
echo "Debug completed!\n";
echo "============================\n";
