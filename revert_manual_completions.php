<?php

/**
 * Script to revert manual completion transactions from today
 * Run this script from the Laravel root directory: php revert_manual_completions.php
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "Starting script to revert today's manual completions...\n";

try {
    // Find all manual completions from today
    $todayTransactions = DB::table('data_subscriptions')
        ->where('data_source', 'manual_completion')
        ->whereDate('created_at', now()->toDateString())
        ->where('status', 'success')
        ->get();

    if ($todayTransactions->isEmpty()) {
        echo "No manual completion transactions found for today.\n";
        exit(0);
    }

    echo "Found {$todayTransactions->count()} manual completion transactions from today.\n";
    echo "Transaction IDs to be deleted:\n";

    foreach ($todayTransactions as $transaction) {
        echo "- {$transaction->transaction_id} (Enumerator ID: {$transaction->enumerator_id}, Phone: {$transaction->phone})\n";
    }

    // Ask for confirmation
    echo "\nAre you sure you want to delete these transactions? (type 'yes' to confirm): ";
    $handle = fopen('php://stdin', 'r');
    $confirmation = trim(fgets($handle));
    fclose($handle);

    if (strtolower($confirmation) !== 'yes') {
        echo "Operation cancelled.\n";
        exit(0);
    }

    $deletedCount = 0;
    $transactionIds = [];

    foreach ($todayTransactions as $transaction) {
        try {
            // Delete the transaction
            DB::table('data_subscriptions')
                ->where('id', $transaction->id)
                ->delete();
            
            $deletedCount++;
            $transactionIds[] = $transaction->transaction_id;

            echo "Deleted transaction: {$transaction->transaction_id}\n";

        } catch (\Exception $e) {
            echo "Error deleting transaction {$transaction->transaction_id}: {$e->getMessage()}\n";
        }
    }

    echo "\nSuccessfully reverted {$deletedCount} manual completion transactions from today.\n";
    echo "Deleted transaction IDs: " . implode(', ', $transactionIds) . "\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}

echo "Script completed successfully.\n";
