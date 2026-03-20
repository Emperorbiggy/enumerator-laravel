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
        Schema::create('data_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->string('phone');
            $table->string('plan_code');
            $table->string('plan_name');
            $table->string('network');
            $table->string('plan_type');
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_before', 10, 2);
            $table->decimal('balance_after', 10, 2);
            $table->text('response_message');
            $table->enum('status', ['success', 'failed', 'pending'])->default('pending');
            $table->json('full_response')->nullable();
            $table->foreignId('enumerator_id')->nullable()->constrained()->onDelete('set null');
            $table->string('admin_id')->nullable();
            $table->timestamps();
            
            $table->index(['phone', 'plan_code']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_subscriptions');
    }
};
