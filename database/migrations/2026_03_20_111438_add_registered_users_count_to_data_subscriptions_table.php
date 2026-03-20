<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('data_subscriptions', function (Blueprint $table) {
            $table->integer('registered_users_count')->default(0)->after('enumerator_id');
            $table->string('data_source')->default('external')->after('registered_users_count'); // 'external' or 'fallback'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['registered_users_count', 'data_source']);
        });
    }
};
