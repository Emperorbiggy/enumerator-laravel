<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Clean up duplicate WhatsApp numbers - keep the first occurrence
        $duplicateWhatsapps = DB::table('enumerators')
            ->select('whatsapp', DB::raw('MIN(id) as min_id'))
            ->groupBy('whatsapp')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('min_id');

        if ($duplicateWhatsapps->isNotEmpty()) {
            DB::table('enumerators')
                ->whereNotIn('id', $duplicateWhatsapps)
                ->whereIn('whatsapp', function($query) {
                    $query->select('whatsapp')
                          ->from('enumerators')
                          ->groupBy('whatsapp')
                          ->havingRaw('COUNT(*) > 1');
                })
                ->delete();
        }

        // Clean up duplicate browsing numbers - keep the first occurrence
        $duplicateBrowsingNumbers = DB::table('enumerators')
            ->select('browsing_number', DB::raw('MIN(id) as min_id'))
            ->groupBy('browsing_number')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('min_id');

        if ($duplicateBrowsingNumbers->isNotEmpty()) {
            DB::table('enumerators')
                ->whereNotIn('id', $duplicateBrowsingNumbers)
                ->whereIn('browsing_number', function($query) {
                    $query->select('browsing_number')
                          ->from('enumerators')
                          ->groupBy('browsing_number')
                          ->havingRaw('COUNT(*) > 1');
                })
                ->delete();
        }

        // Clean up duplicate account numbers - keep the first occurrence
        $duplicateAccountNumbers = DB::table('enumerators')
            ->select('account_number', DB::raw('MIN(id) as min_id'))
            ->groupBy('account_number')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('min_id');

        if ($duplicateAccountNumbers->isNotEmpty()) {
            DB::table('enumerators')
                ->whereNotIn('id', $duplicateAccountNumbers)
                ->whereIn('account_number', function($query) {
                    $query->select('account_number')
                          ->from('enumerators')
                          ->groupBy('account_number')
                          ->havingRaw('COUNT(*) > 1');
                })
                ->delete();
        }

        // Now add the unique constraints
        Schema::table('enumerators', function (Blueprint $table) {
            $table->unique('whatsapp', 'enumerators_whatsapp_unique');
            $table->unique('browsing_number', 'enumerators_browsing_number_unique');
            $table->unique('account_number', 'enumerators_account_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enumerators', function (Blueprint $table) {
            $table->dropUnique('enumerators_whatsapp_unique');
            $table->dropUnique('enumerators_browsing_number_unique');
            $table->dropUnique('enumerators_account_number_unique');
        });
    }
};
