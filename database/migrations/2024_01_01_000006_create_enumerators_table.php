<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enumerators', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('whatsapp');
            $table->string('state')->default('Osun');
            $table->string('lga');
            $table->string('ward');
            $table->string('polling_unit');
            $table->string('browsing_network');
            $table->string('browsing_number');
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('account_number');
            $table->string('group_name');
            $table->string('coordinator_phone');
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enumerators');
    }
};
