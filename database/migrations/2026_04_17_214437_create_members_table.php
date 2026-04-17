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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('nin')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('gender');
            $table->date('date_of_birth');
            $table->string('phone_number');
            $table->string('email')->nullable();
            $table->string('state');
            $table->string('lga');
            $table->string('ward');
            $table->string('polling_unit');
            $table->text('residential_address');
            $table->string('photo_path')->nullable();
            $table->string('membership_number')->unique();
            $table->string('qr_code_path')->nullable();
            $table->date('registration_date');
            $table->string('agentcode');
            $table->timestamps();

            // Indexes
            $table->index('nin', 'idx_nin');
            $table->index('membership_number', 'idx_membership_number');
            $table->index('phone_number', 'idx_phone_number');
            $table->index('state', 'idx_state');
            $table->index('lga', 'idx_lga');
            $table->index('ward', 'idx_ward');
            $table->index('polling_unit', 'idx_polling_unit');
            $table->index('registration_date', 'idx_registration_date');
            $table->index('email', 'idx_email');
            $table->index(['state', 'lga'], 'idx_state_lga');
            $table->index(['state', 'lga', 'ward'], 'idx_state_lga_ward');
            $table->index('agentcode', 'idx_agentcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
